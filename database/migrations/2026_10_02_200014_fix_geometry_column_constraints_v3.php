<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Perbaikan ditemukan saat menyusun Fase 2 (command migrasi data):
 *
 * 1. `layers_v3.bbox` dibuat dengan typmod ketat geometry(Polygon,4326).
 *    ST_Envelope() atas layer dengan 1 fitur (atau semua fitur segaris)
 *    menghasilkan POINT/LINESTRING, bukan POLYGON — akan ditolak PostgreSQL
 *    saat UPDATE cache bbox (Fase 2 tahap "cache"). Direlaksasi jadi
 *    geometry(Geometry,4326) generik + CHECK SRID, meniru pola `extent`/
 *    `center_point` di spatial_layers (v2) yang sengaja tidak di-subtype.
 *
 * 2. `spatial_features_v3.geom` ternyata tidak ter-enforce SRID 4326 di
 *    level kolom (typmod Laravel geometry() tidak menerapkan srid tanpa
 *    subtype eksplisit) — ditambahkan CHECK eksplisit sesuai konvensi
 *    dokumen §4 ("SRID penyimpanan: EPSG:4326").
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE layers_v3 ALTER COLUMN bbox TYPE geometry(Geometry, 4326) USING bbox::geometry(Geometry, 4326)');
        DB::statement('ALTER TABLE layers_v3 ADD CONSTRAINT ck_layers_v3_bbox_srid CHECK (bbox IS NULL OR ST_SRID(bbox) = 4326)');
        DB::statement('ALTER TABLE spatial_features_v3 ADD CONSTRAINT ck_spatial_features_v3_srid CHECK (ST_SRID(geom) = 4326)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE spatial_features_v3 DROP CONSTRAINT IF EXISTS ck_spatial_features_v3_srid');
        DB::statement('ALTER TABLE layers_v3 DROP CONSTRAINT IF EXISTS ck_layers_v3_bbox_srid');
        DB::statement('ALTER TABLE layers_v3 ALTER COLUMN bbox TYPE geometry(Polygon, 4326) USING bbox::geometry(Polygon, 4326)');
    }
};
