<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Versi data Peta Interaktif (Layer published V3 beserta feature, gaya, dan kategorinya).
 *
 * Klien menyimpan data peta di cache browser (IndexedDB) dengan TTL 24 jam. Versi ini dipakai
 * untuk skenario kedua: begitu ada data atau kategori yang ditambah, diubah, atau dihapus,
 * versinya berubah dan cache di browser dibuang.
 *
 * Versi dihitung dari isi database (jumlah, updated_at terbaru, dan penempatan Layer),
 * sehingga ikut berubah walau perubahan dilakukan lewat query massal atau impor yang tidak
 * memicu event model. Hasilnya di-cache singkat di server dan dihapus segera oleh event model.
 */
class MapDataVersion
{
    public const CACHE_KEY = 'map.tematik.data_version';

    private const SERVER_CACHE_SECONDS = 15;

    /**
     * Naikkan bila bentuk payload /geojson berubah (mis. properti baru seperti
     * style_override), supaya cache browser yang berisi format lama ikut dibuang.
     */
    private const PAYLOAD_VERSION = 3;

    public static function current(): string
    {
        return Cache::remember(self::CACHE_KEY, self::SERVER_CACHE_SECONDS, fn () => self::compute());
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Dihitung dari skema V3 yang dibaca peta publik (lihat PublicMapCatalog): feature,
     * Layer published, gaya, dan pohon kategori/node.
     */
    private static function compute(): string
    {
        $features = DB::table('spatial_features as sf')
            ->join('layers as l', 'l.id', '=', 'sf.layer_id')
            ->where('l.status', 'published')
            ->whereNull('l.deleted_at')
            ->selectRaw('count(*) as total, max(sf.updated_at) as latest, coalesce(sum(sf.id), 0) as id_sum')
            ->first();

        $layers = DB::table('layers')
            ->where('status', 'published')
            ->whereNull('deleted_at')
            ->selectRaw("count(*) as total, max(updated_at) as latest, string_agg(id::text || ':' || coalesce(category_node_id::text, category_id::text), ',' order by id) as placement")
            ->first();

        $styles = DB::table('layer_styles')->selectRaw('count(*) as total, max(updated_at) as latest')->first();

        $categories = [
            DB::table('categories_v3')->selectRaw('count(*) as total, max(updated_at) as latest, max(deleted_at) as deleted')->first(),
            DB::table('category_nodes')->selectRaw('count(*) as total, max(updated_at) as latest, max(deleted_at) as deleted')->first(),
        ];

        return md5(json_encode([self::PAYLOAD_VERSION, $features, $layers, $styles, $categories]));
    }
}
