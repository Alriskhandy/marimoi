<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rekonsiliasi backfill (docs/marimoi v2/04_implementation/
 * 12-implementasi-perbaikan-pemetaan.md Bagian 1.6 langkah 2): spatial_layers/
 * spatial_layer_features adalah backfill SATU KALI dari categories/data_spatial
 * (Prioritas 1-2, 2026-09-27). Command ini menangkap categories/data_spatial yang
 * dibuat SETELAH backfill awal — idempoten lewat NOT EXISTS, aman dijalankan
 * berkali-kali. Dijalankan SETELAH marimoi:granularize-map-types (urutan wajib),
 * lalu memanggilnya lagi di akhir supaya Layer baru yang jadi akar ikut
 * digranularisasi juga.
 */
class ReconcileSpatialLayersBackfill extends Command
{
    protected $signature = 'marimoi:reconcile-spatial-layers-backfill {--dry-run}';

    protected $description = 'Backfill ulang categories/data_spatial yang dibuat setelah backfill awal Prioritas 1-2, lalu granularisasi ulang';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $newCategoriesCount = DB::table('categories as c')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('spatial_layers as sl')->whereColumn('sl.legacy_category_id', 'c.id'))
            ->count();

        $newDataSpatialCount = DB::table('data_spatial as ds')
            ->whereNotNull('ds.kategori_id')
            ->whereNotNull('ds.geom')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('spatial_layer_features as slf')->whereColumn('slf.legacy_data_spatial_id', 'ds.id'))
            ->count();

        $this->info("Categories baru yang akan ter-backfill ke spatial_layers: {$newCategoriesCount}");
        $this->info("Data spatial baru yang akan ter-backfill ke spatial_layer_features: {$newDataSpatialCount}");

        if ($dryRun) {
            $this->comment('--dry-run: tidak ada perubahan disimpan.');

            return self::SUCCESS;
        }

        if ($newCategoriesCount > 0) {
            $this->backfillNewSpatialLayers();
        }

        if ($newDataSpatialCount > 0) {
            $this->backfillNewSpatialLayerFeatures();
        }

        $this->call('marimoi:granularize-map-types');

        return self::SUCCESS;
    }

    private function backfillNewSpatialLayers(): void
    {
        DB::statement("
            INSERT INTO spatial_layers
                (public_id, slug, name, title, description, map_type_id,
                 owner_user_id, visibility, is_active, is_group,
                 legacy_category_id, created_at, updated_at)
            SELECT
                gen_random_uuid(),
                'layer-' || c.id || '-' || regexp_replace(lower(c.nama), '[^a-z0-9]+', '-', 'g'),
                c.nama,
                c.nama,
                c.deskripsi,
                mt.id,
                c.user_id,
                'private',
                COALESCE(c.is_active, false),
                false,
                c.id,
                c.created_at,
                c.updated_at
            FROM categories c
            LEFT JOIN map_types mt ON mt.slug = c.type
            WHERE NOT EXISTS (SELECT 1 FROM spatial_layers sl WHERE sl.legacy_category_id = c.id)
        ");

        DB::statement('
            UPDATE spatial_layers sl
            SET parent_id = parent_sl.id
            FROM categories c
            JOIN spatial_layers parent_sl ON parent_sl.legacy_category_id = c.parent_id
            WHERE sl.legacy_category_id = c.id AND c.parent_id IS NOT NULL AND sl.parent_id IS NULL
        ');

        DB::statement('
            UPDATE spatial_layers sl
            SET color = c.warna,
                is_marker = c.is_marker,
                thumbnail_path = c.gambar
            FROM categories c
            WHERE sl.legacy_category_id = c.id AND sl.color IS NULL AND sl.is_marker = false
        ');
    }

    private function backfillNewSpatialLayerFeatures(): void
    {
        DB::statement('
            INSERT INTO spatial_layer_features
                (spatial_layer_id, external_id, geometry, attributes, created_by, legacy_data_spatial_id, created_at, updated_at)
            SELECT
                sl.id,
                ds.uuid,
                ds.geom,
                ds.dbf_attributes,
                ds.user_id,
                ds.id,
                ds.created_at,
                ds.updated_at
            FROM data_spatial ds
            JOIN spatial_layers sl ON sl.legacy_category_id = ds.kategori_id
            WHERE ds.kategori_id IS NOT NULL AND ds.geom IS NOT NULL
              AND NOT EXISTS (SELECT 1 FROM spatial_layer_features slf WHERE slf.legacy_data_spatial_id = ds.id)
        ');
    }
}
