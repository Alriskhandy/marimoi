/**
 * MapDataStore - IndexedDB Cache System untuk GeoJSON data
 * Menyediakan caching dengan TTL 24 jam untuk performa optimal
 */
class MapDataStore {
    constructor() {
        this.dbName = "marimoi_map_cache";
        this.version = 1;
        this.db = null;
        // this.TTL = 10 * 1000; // 24 jam dalam ms
        this.TTL = 24 * 60 * 60 * 1000; // 24 jam dalam ms
        this.VERSION_KEY = "__map_data_version__";
        this.debug =
            window.location.hostname === "localhost" ||
            window.location.hostname === "127.0.0.1";

        // `ready` menunggu IndexedDB terbuka, supaya baca/tulis cache pertama tidak gagal karena balapan.
        this.ready = this.init();
    }

    async init() {
        try {
            this.db = await this.openDB();
            await this.cleanExpiredData();
            if (this.debug)
                console.log("MapDataStore initialized successfully");
        } catch (error) {
            console.warn(
                "IndexedDB not available, falling back to network only:",
                error
            );
        }
    }

    openDB() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.version);

            request.onerror = () => reject(request.error);
            request.onsuccess = () => resolve(request.result);

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                // Object store untuk GeoJSON cache
                if (!db.objectStoreNames.contains("geojson_cache")) {
                    const store = db.createObjectStore("geojson_cache", {
                        keyPath: "key",
                    });
                    store.createIndex("timestamp", "timestamp", {
                        unique: false,
                    });
                    store.createIndex("category", "category", {
                        unique: false,
                    });
                }

                // Object store untuk metadata cache
                if (!db.objectStoreNames.contains("metadata_cache")) {
                    const metaStore = db.createObjectStore("metadata_cache", {
                        keyPath: "key",
                    });
                    metaStore.createIndex("timestamp", "timestamp", {
                        unique: false,
                    });
                }
            };
        });
    }

    generateCacheKey(params) {
        const { type, sub_type, year, category, limit, offset } = params;
        return `${type || "default"}_${sub_type || "none"}_${
            year || "all"
        }_${category}_${limit || "unlimited"}_${offset || 0}`;
    }

    async getCachedData(cacheKey) {
        await this.ready;
        if (!this.db) return null;

        try {
            const transaction = this.db.transaction(
                ["geojson_cache"],
                "readonly"
            );
            const store = transaction.objectStore("geojson_cache");
            const request = store.get(cacheKey);

            return new Promise((resolve, reject) => {
                request.onsuccess = () => {
                    const result = request.result;
                    if (!result) {
                        resolve(null);
                        return;
                    }

                    // Check TTL
                    const now = Date.now();
                    if (now - result.timestamp > this.TTL) {
                        // Data expired, remove it
                        this.removeCachedData(cacheKey);
                        resolve(null);
                        return;
                    }

                    if (this.debug) console.log(`Cache HIT for ${cacheKey}`);
                    resolve(result.data);
                };
                request.onerror = () => reject(request.error);
            });
        } catch (error) {
            console.warn("Error reading from cache:", error);
            return null;
        }
    }

    async setCachedData(cacheKey, data, category) {
        await this.ready;
        if (!this.db) return;

        try {
            const transaction = this.db.transaction(
                ["geojson_cache"],
                "readwrite"
            );
            const store = transaction.objectStore("geojson_cache");

            const cacheEntry = {
                key: cacheKey,
                data: data,
                timestamp: Date.now(),
                category: category,
                size: JSON.stringify(data).length,
            };

            await store.put(cacheEntry);
            if (this.debug)
                console.log(
                    `Cache SET for ${cacheKey} (${Math.round(
                        cacheEntry.size / 1024
                    )}KB)`
                );
        } catch (error) {
            console.warn("Error writing to cache:", error);
        }
    }

    /**
     * Cache metadata (daftar kategori). Memakai TTL yang sama dengan data GeoJSON.
     */
    async getCachedMetadata(key) {
        await this.ready;
        if (!this.db) return null;

        try {
            const store = this.db
                .transaction(["metadata_cache"], "readonly")
                .objectStore("metadata_cache");
            const request = store.get(key);

            return await new Promise((resolve) => {
                request.onsuccess = () => {
                    const result = request.result;
                    if (!result || Date.now() - result.timestamp > this.TTL) {
                        resolve(null);
                        return;
                    }
                    if (this.debug) console.log(`Metadata cache HIT for ${key}`);
                    resolve(result.data);
                };
                request.onerror = () => resolve(null);
            });
        } catch (error) {
            console.warn("Error reading metadata cache:", error);
            return null;
        }
    }

    async setCachedMetadata(key, data) {
        await this.ready;
        if (!this.db) return;

        try {
            const store = this.db
                .transaction(["metadata_cache"], "readwrite")
                .objectStore("metadata_cache");
            store.put({ key, data, timestamp: Date.now() });
        } catch (error) {
            console.warn("Error writing metadata cache:", error);
        }
    }

    async removeCachedData(cacheKey) {
        if (!this.db) return;

        try {
            const transaction = this.db.transaction(
                ["geojson_cache"],
                "readwrite"
            );
            const store = transaction.objectStore("geojson_cache");
            await store.delete(cacheKey);
            if (this.debug) console.log(`Cache REMOVE for ${cacheKey}`);
        } catch (error) {
            console.warn("Error removing from cache:", error);
        }
    }

    async cleanExpiredData() {
        if (!this.db) return;

        try {
            const transaction = this.db.transaction(
                ["geojson_cache"],
                "readwrite"
            );
            const store = transaction.objectStore("geojson_cache");
            const index = store.index("timestamp");

            const now = Date.now();
            const expiredThreshold = now - this.TTL;

            const request = index.openCursor(
                IDBKeyRange.upperBound(expiredThreshold)
            );
            let cleanedCount = 0;

            request.onsuccess = (event) => {
                const cursor = event.target.result;
                if (cursor) {
                    cursor.delete();
                    cleanedCount++;
                    cursor.continue();
                } else {
                    if (cleanedCount > 0 && this.debug) {
                        console.log(
                            `Cleaned ${cleanedCount} expired cache entries`
                        );
                    }
                }
            };
        } catch (error) {
            console.warn("Error cleaning expired data:", error);
        }
    }

    async getCacheStats() {
        if (!this.db) return { totalEntries: 0, totalSize: 0 };

        try {
            const transaction = this.db.transaction(
                ["geojson_cache"],
                "readonly"
            );
            const store = transaction.objectStore("geojson_cache");
            const request = store.getAll();

            return new Promise((resolve) => {
                request.onsuccess = () => {
                    const entries = request.result;
                    const totalSize = entries.reduce(
                        (sum, entry) => sum + (entry.size || 0),
                        0
                    );
                    resolve({
                        totalEntries: entries.length,
                        totalSize: totalSize,
                        totalSizeMB:
                            Math.round((totalSize / 1024 / 1024) * 100) / 100,
                    });
                };
                request.onerror = () =>
                    resolve({ totalEntries: 0, totalSize: 0 });
            });
        } catch (error) {
            return { totalEntries: 0, totalSize: 0 };
        }
    }

    /**
     * Sinkronkan cache dengan versi data di server (skenario "data berubah").
     * Bila versi berbeda dengan yang tersimpan, seluruh cache (GeoJSON + metadata kategori)
     * dibuang. TTL 24 jam tetap berlaku sebagai skenario kedua. Bila server tidak terjangkau,
     * cache yang ada dipakai apa adanya. Mengembalikan true bila cache dibuang.
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
            // Segarkan penanda agar tidak kedaluwarsa lebih cepat dari data yang di-cache.
            await this.setCachedMetadata(this.VERSION_KEY, version);
            return false;
        }

        // Versi berbeda (atau belum pernah tercatat): buang cache lama lalu catat versi baru.
        await this.clearAllCache();
        await this.setCachedMetadata(this.VERSION_KEY, version);

        if (this.debug) console.log(`Cache dibuang: versi data berubah (${stored} -> ${version})`);
        return stored !== null;
    }

    async clearAllCache() {
        await this.ready;
        if (!this.db) return;

        try {
            const transaction = this.db.transaction(
                ["geojson_cache", "metadata_cache"],
                "readwrite"
            );
            transaction.objectStore("geojson_cache").clear();
            transaction.objectStore("metadata_cache").clear();
            await new Promise((resolve) => {
                transaction.oncomplete = resolve;
                transaction.onerror = resolve;
                transaction.onabort = resolve;
            });
            if (this.debug) console.log("All cache cleared");
        } catch (error) {
            console.warn("Error clearing cache:", error);
        }
    }
}

// Export untuk digunakan di map.js
window.MapDataStore = MapDataStore;
