<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Versi data Peta Tematik (data spasial bertipe tematik + kategori bertipe tematik).
 *
 * Klien menyimpan data peta di cache browser (IndexedDB) dengan TTL 24 jam. Versi ini dipakai
 * untuk skenario kedua: begitu ada data atau kategori yang ditambah, diubah, atau dihapus,
 * versinya berubah dan cache di browser dibuang.
 *
 * Versi dihitung dari isi database (jumlah, updated_at terbaru, dan penjumlahan kategori_id),
 * sehingga ikut berubah walau perubahan dilakukan lewat query massal atau impor yang tidak
 * memicu event model. Hasilnya di-cache singkat di server dan dihapus segera oleh event model.
 */
class MapDataVersion
{
    public const CACHE_KEY = 'map.tematik.data_version';

    private const SERVER_CACHE_SECONDS = 15;

    public static function current(): string
    {
        return Cache::remember(self::CACHE_KEY, self::SERVER_CACHE_SECONDS, fn () => self::compute());
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private static function compute(): string
    {
        $data = DB::table('data_spatial')
            ->where('data_type', 'tematik')
            ->selectRaw('count(*) as total, max(updated_at) as latest, coalesce(sum(kategori_id), 0) as category_sum, coalesce(sum(id), 0) as id_sum')
            ->first();

        $categories = DB::table('categories')
            ->where('type', 'tematik')
            ->selectRaw('count(*) as total, max(updated_at) as latest, coalesce(sum(id), 0) as id_sum, coalesce(sum(parent_id), 0) as parent_sum')
            ->first();

        return md5(json_encode([$data, $categories]));
    }
}
