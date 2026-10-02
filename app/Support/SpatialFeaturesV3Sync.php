<?php

namespace App\Support;

use App\Models\DataSpatial;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sinkronisasi real-time `data_spatial_legacy_v1` (lama, masih ditulis aktif
 * oleh DataSpatialController) ke `spatial_features` — supaya endpoint publik
 * `/geojson` (FrontendController, direpoint ke v3 di plan mellow-weaving-eclipse
 * Fase 4) langsung menampilkan data baru tanpa menunggu
 * `marimoi:migrate-schema-v3` dijalankan manual lagi.
 *
 * Dipasang lewat event model DataSpatial::created/updated/deleted di
 * AppServiceProvider — bukan dipanggil manual dari DataSpatialController —
 * supaya mencakup SEMUA jalur penulisan (shapefile/coordinates/kmz/bulk
 * update) tanpa perlu menyentuh controller itu (sengaja belum direwrite ke
 * v3, lihat plan Fase 5).
 */
class SpatialFeaturesV3Sync
{
    public static function upsert(DataSpatial $dataSpatial): void
    {
        if (! $dataSpatial->kategori_id) {
            return;
        }

        $layerId = self::resolveLayerId((int) $dataSpatial->kategori_id);

        if (! $layerId) {
            return;
        }

        $opdName = $dataSpatial->opd_pengelola_id
            ? DB::table('opd')->where('id', $dataSpatial->opd_pengelola_id)->value('name')
            : null;

        $properties = array_merge($dataSpatial->dbf_attributes ?? [], array_filter([
            'sumber_data' => $dataSpatial->sumber_data,
            'opd_penanggung_jawab' => $opdName,
            'tanggal_data' => $dataSpatial->tanggal_data?->toDateString(),
            'tahun' => $dataSpatial->tahun,
        ], fn ($value) => $value !== null));

        $existingId = DB::table('spatial_features')->where('legacy_data_spatial_id', $dataSpatial->id)->value('id');

        $validGeom = DB::selectOne(
            'SELECT NOT ST_IsEmpty(ST_MakeValid(geom)) AS valid FROM data_spatial_legacy_v1 WHERE id = ?',
            [$dataSpatial->id]
        )?->valid ?? false;

        if (! $validGeom) {
            return;
        }

        if ($existingId) {
            DB::update(
                <<<'SQL'
                    UPDATE spatial_features sfv
                    SET layer_id = ?, properties = ?::jsonb, gambar = ds.gambar,
                        geom = ST_Force2D(ST_MakeValid(ds.geom)), updated_at = now()
                    FROM data_spatial_legacy_v1 ds
                    WHERE ds.id = ? AND sfv.id = ?
                    SQL,
                [$layerId, json_encode($properties), $dataSpatial->id, $existingId]
            );
        } else {
            DB::insert(
                <<<'SQL'
                    INSERT INTO spatial_features
                        (layer_id, geom, properties, gambar, created_by, legacy_data_spatial_id, created_at, updated_at)
                    SELECT ?, ST_Force2D(ST_MakeValid(ds.geom)), ?::jsonb, ds.gambar, ds.user_id, ds.id, ds.created_at, now()
                    FROM data_spatial_legacy_v1 ds
                    WHERE ds.id = ?
                    SQL,
                [$layerId, json_encode($properties), $dataSpatial->id]
            );
        }

        self::refreshLayerCache($layerId);
    }

    public static function delete(DataSpatial $dataSpatial): void
    {
        $layerId = DB::table('spatial_features')
            ->where('legacy_data_spatial_id', $dataSpatial->id)
            ->value('layer_id');

        DB::table('spatial_features')->where('legacy_data_spatial_id', $dataSpatial->id)->delete();

        if ($layerId) {
            self::refreshLayerCache($layerId);
        }
    }

    private static function resolveLayerId(int $legacyCategoryId): ?string
    {
        $existing = DB::table('layers')->where('legacy_category_id', $legacyCategoryId)->value('id');

        if ($existing) {
            return $existing;
        }

        $legacyCategory = DB::table('categories_legacy_v1')->where('id', $legacyCategoryId)->first();

        if (! $legacyCategory) {
            return null;
        }

        $categoryId = DB::table('categories_v3')->where('legacy_category_id', $legacyCategoryId)->value('id');
        $categoryNodeId = null;

        if (! $categoryId) {
            $node = DB::table('category_nodes')->where('legacy_category_id', $legacyCategoryId)->first();

            if (! $node) {
                // Kategori lama ini belum (atau tidak pernah) terbawa migrasi
                // Fase 2 ke categories_v3/category_nodes (mis. kategori yang
                // baru dibuat setelah migrasi terakhir jalan, atau data uji).
                // Dibuat sebagai root v3 seadanya — tree lengkap tetap jadi
                // tanggung jawab marimoi:migrate-schema-v3, bridge ini murni
                // supaya datanya tidak hilang dari peta publik.
                $categoryId = DB::table('categories_v3')->insertGetId([
                    'id' => (string) Str::uuid(),
                    'code' => 'legacy-cat-'.$legacyCategoryId,
                    'name' => $legacyCategory->nama,
                    'slug' => 'legacy-cat-'.$legacyCategoryId.'-'.Str::lower(Str::random(6)),
                    'type' => $legacyCategory->type,
                    'is_active' => true,
                    'legacy_category_id' => $legacyCategoryId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], 'id');
            } else {
                $categoryId = $node->category_id;
                $categoryNodeId = $node->id;
            }
        }

        $mapTypeId = DB::table('map_types')->where('slug', $legacyCategory->type)->value('id');
        $id = (string) Str::uuid();

        DB::table('layers')->insert([
            'id' => $id,
            'category_id' => $categoryId,
            'category_node_id' => $categoryNodeId,
            'layer_type_id' => 4,
            'map_type_id' => $mapTypeId,
            'code' => 'legacy-sync-'.Str::lower(Str::random(10)),
            'slug' => 'legacy-sync-'.Str::lower(Str::random(10)),
            'name' => $legacyCategory->nama,
            'status' => 'published',
            'published_at' => now(),
            'legacy_category_id' => $legacyCategoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    private static function refreshLayerCache(string $layerId): void
    {
        DB::update(
            <<<'SQL'
                UPDATE layers lv
                SET feature_count = sub.cnt, bbox = sub.bbox
                FROM (
                    SELECT COUNT(*) AS cnt, ST_Envelope(ST_Collect(geom)) AS bbox
                    FROM spatial_features WHERE layer_id = ?
                ) sub
                WHERE lv.id = ?
                SQL,
            [$layerId, $layerId]
        );
    }
}
