/**
 * map-cache.js — MapDataStore: cache data peta di IndexedDB browser.
 *
 * Menyimpan potongan GeoJSON per mapset (store "geojson_cache") dan daftar mapset
 * (store "metadata_cache") supaya kunjungan berikutnya tidak perlu mengunduh ulang.
 * Cache dibuang bila:
 *   1. sudah lebih dari 24 jam (TTL), atau
 *   2. versi data di server berubah (syncVersion, dari endpoint /geojson/version).
 *
 * Bila IndexedDB tidak tersedia, semua method mengembalikan "tidak ada cache" sehingga
 * peta tetap berjalan dengan mengambil data langsung dari jaringan.
 */
class MapDataStore {
    constructor() {
        this.dbName = "marimoi_map_cache";
        this.version = 1;
        this.db = null;
        this.TTL = 24 * 60 * 60 * 1000;
        // Kunci khusus di metadata_cache untuk menyimpan versi data terakhir dari server.
        this.VERSION_KEY = "__map_data_version__";

        // Semua method menunggu `ready` supaya akses pertama tidak mendahului IndexedDB terbuka.
        this.ready = this.init();
    }

    async init() {
        try {
            this.db = await this.openDB();
            await this.cleanExpiredData();
        } catch (error) {
            console.warn("IndexedDB tidak tersedia, data peta diambil langsung dari jaringan:", error);
        }
    }

    openDB() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.version);

            request.onerror = () => reject(request.error);
            request.onsuccess = () => resolve(request.result);

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                if (!db.objectStoreNames.contains("geojson_cache")) {
                    const store = db.createObjectStore("geojson_cache", { keyPath: "key" });
                    store.createIndex("timestamp", "timestamp", { unique: false });
                    store.createIndex("category", "category", { unique: false });
                }

                if (!db.objectStoreNames.contains("metadata_cache")) {
                    const metaStore = db.createObjectStore("metadata_cache", { keyPath: "key" });
                    metaStore.createIndex("timestamp", "timestamp", { unique: false });
                }
            };
        });
    }

    /**
     * Kunci cache satu potongan data: jenis data + mapset + limit/offset potongan.
     */
    generateCacheKey(params) {
        const { type, sub_type, year, category, limit, offset } = params;
        return `${type || "default"}_${sub_type || "none"}_${year || "all"}_${category}_${limit || "unlimited"}_${offset || 0}`;
    }

    /**
     * Potongan GeoJSON dari cache, atau null bila belum ada / sudah kedaluwarsa.
     */
    async getCachedData(cacheKey) {
        await this.ready;
        if (!this.db) return null;

        try {
            const request = this.db.transaction(["geojson_cache"], "readonly").objectStore("geojson_cache").get(cacheKey);

            return new Promise((resolve, reject) => {
                request.onsuccess = () => {
                    const result = request.result;
                    if (!result) {
                        resolve(null);
                        return;
                    }

                    if (Date.now() - result.timestamp > this.TTL) {
                        this.removeCachedData(cacheKey);
                        resolve(null);
                        return;
                    }

                    resolve(result.data);
                };
                request.onerror = () => reject(request.error);
            });
        } catch (error) {
            console.warn("Gagal membaca cache peta:", error);
            return null;
        }
    }

    async setCachedData(cacheKey, data, category) {
        await this.ready;
        if (!this.db) return;

        try {
            const store = this.db.transaction(["geojson_cache"], "readwrite").objectStore("geojson_cache");
            await store.put({
                key: cacheKey,
                data,
                timestamp: Date.now(),
                category,
                size: JSON.stringify(data).length,
            });
        } catch (error) {
            console.warn("Gagal menyimpan cache peta:", error);
        }
    }

    /**
     * Daftar mapset (dan penanda versi) dari cache; TTL sama dengan data GeoJSON.
     */
    async getCachedMetadata(key) {
        await this.ready;
        if (!this.db) return null;

        try {
            const request = this.db.transaction(["metadata_cache"], "readonly").objectStore("metadata_cache").get(key);

            return await new Promise((resolve) => {
                request.onsuccess = () => {
                    const result = request.result;
                    if (!result || Date.now() - result.timestamp > this.TTL) {
                        resolve(null);
                        return;
                    }
                    resolve(result.data);
                };
                request.onerror = () => resolve(null);
            });
        } catch (error) {
            console.warn("Gagal membaca cache daftar mapset:", error);
            return null;
        }
    }

    async setCachedMetadata(key, data) {
        await this.ready;
        if (!this.db) return;

        try {
            this.db.transaction(["metadata_cache"], "readwrite").objectStore("metadata_cache").put({ key, data, timestamp: Date.now() });
        } catch (error) {
            console.warn("Gagal menyimpan cache daftar mapset:", error);
        }
    }

    async removeCachedData(cacheKey) {
        if (!this.db) return;

        try {
            await this.db.transaction(["geojson_cache"], "readwrite").objectStore("geojson_cache").delete(cacheKey);
        } catch (error) {
            console.warn("Gagal menghapus cache peta:", error);
        }
    }

    /**
     * Hapus semua potongan GeoJSON yang sudah lewat TTL (dijalankan saat halaman dibuka).
     */
    async cleanExpiredData() {
        if (!this.db) return;

        try {
            const index = this.db.transaction(["geojson_cache"], "readwrite").objectStore("geojson_cache").index("timestamp");
            const request = index.openCursor(IDBKeyRange.upperBound(Date.now() - this.TTL));

            request.onsuccess = (event) => {
                const cursor = event.target.result;
                if (cursor) {
                    cursor.delete();
                    cursor.continue();
                }
            };
        } catch (error) {
            console.warn("Gagal membersihkan cache kedaluwarsa:", error);
        }
    }

    /**
     * Bandingkan versi data di server dengan versi yang tersimpan; bila berbeda, seluruh
     * cache dibuang. Bila server tidak terjangkau (maks. 4 detik), cache dipakai apa adanya.
     *
     * @returns {Promise<boolean>} true bila cache lama dibuang karena versi berubah
     */
    async syncVersion(url) {
        await this.ready;
        if (!this.db || !url) return false;

        let version = null;
        try {
            const controller = new AbortController();
            const timer = setTimeout(() => controller.abort(), 4000);
            const response = await fetch(url, {
                cache: "no-store",
                signal: controller.signal,
                headers: { Accept: "application/json" },
            });
            clearTimeout(timer);

            if (response.ok) {
                version = (await response.json())?.version || null;
            }
        } catch (error) {
            return false;
        }

        if (!version) return false;

        const stored = await this.getCachedMetadata(this.VERSION_KEY);
        if (stored === version) {
            // Simpan ulang supaya penanda versi tidak kedaluwarsa lebih dulu daripada datanya.
            await this.setCachedMetadata(this.VERSION_KEY, version);
            return false;
        }

        await this.clearAllCache();
        await this.setCachedMetadata(this.VERSION_KEY, version);
        return stored !== null;
    }

    async clearAllCache() {
        await this.ready;
        if (!this.db) return;

        try {
            const transaction = this.db.transaction(["geojson_cache", "metadata_cache"], "readwrite");
            transaction.objectStore("geojson_cache").clear();
            transaction.objectStore("metadata_cache").clear();
            await new Promise((resolve) => {
                transaction.oncomplete = resolve;
                transaction.onerror = resolve;
                transaction.onabort = resolve;
            });
        } catch (error) {
            console.warn("Gagal mengosongkan cache peta:", error);
        }
    }
}

window.MapDataStore = MapDataStore;
