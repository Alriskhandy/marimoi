<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Skema v3 §5.11, nama sementara `spatial_features_v3` (lihat catatan di
 * create_categories_v3_table.php).
 *
 * Deviasi dari dokumen (plan Keputusan #6): `region_id` dipertahankan
 * sebagai kolom tambahan — dipakai aktif oleh endpoint publik peta-v2
 * (SpatialMapController), bukan kolom mati.
 *
 * `legacy_data_spatial_id`/`legacy_spatial_layer_feature_id` murni jejak
 * migrasi data (Fase 2), dihapus di migration terpisah setelah skema v3
 * stabil di produksi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spatial_features_v3', function (Blueprint $table) {
            $table->id();
            $table->uuid('layer_id');
            $table->uuid('layer_import_id')->nullable();
            $table->string('source_fid', 100)->nullable();
            $table->geometry('geom', srid: 4326);
            $table->jsonb('properties')->default('{}');
            $table->string('label', 255)->nullable();
            $table->unsignedBigInteger('region_id')->nullable();
            $table->unsignedBigInteger('legacy_data_spatial_id')->nullable();
            $table->unsignedBigInteger('legacy_spatial_layer_feature_id')->nullable();
            $table->timestamps();

            $table->foreign('layer_id')->references('id')->on('layers_v3')->cascadeOnDelete();
            $table->foreign('region_id')->references('id')->on('administrative_regions')->nullOnDelete();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE spatial_features_v3 ADD CONSTRAINT fk_spatial_features_v3_import
                FOREIGN KEY (layer_import_id, layer_id)
                REFERENCES layer_imports (id, layer_id)
                ON DELETE SET NULL (layer_import_id)
            SQL);
        DB::statement('ALTER TABLE spatial_features_v3 ADD CONSTRAINT ck_spatial_features_v3_valid CHECK (ST_IsValid(geom) AND NOT ST_IsEmpty(geom))');
        DB::statement('ALTER TABLE spatial_features_v3 ADD COLUMN geom_type text GENERATED ALWAYS AS (ST_GeometryType(geom)) STORED');
        DB::statement(<<<'SQL'
            ALTER TABLE spatial_features_v3 ADD COLUMN area_m2 double precision GENERATED ALWAYS AS (
                CASE WHEN ST_Dimension(geom) = 2 THEN ST_Area(geom::geography) END) STORED
            SQL);
        DB::statement(<<<'SQL'
            ALTER TABLE spatial_features_v3 ADD COLUMN length_m double precision GENERATED ALWAYS AS (
                CASE WHEN ST_Dimension(geom) = 1 THEN ST_Length(geom::geography) END) STORED
            SQL);
        DB::statement('ALTER TABLE spatial_features_v3 ADD COLUMN search_text tsvector');

        DB::statement('CREATE INDEX ix_spatial_features_v3_geom ON spatial_features_v3 USING gist (geom)');
        DB::statement('CREATE INDEX ix_spatial_features_v3_layer ON spatial_features_v3 (layer_id)');
        DB::statement('CREATE INDEX ix_spatial_features_v3_import ON spatial_features_v3 (layer_import_id)');
        DB::statement('CREATE INDEX ix_spatial_features_v3_properties ON spatial_features_v3 USING gin (properties jsonb_path_ops)');
        DB::statement('CREATE INDEX ix_spatial_features_v3_search ON spatial_features_v3 USING gin (search_text)');
        DB::statement('CREATE INDEX ix_spatial_features_v3_legacy_data_spatial ON spatial_features_v3 (legacy_data_spatial_id)');
        DB::statement('CREATE INDEX ix_spatial_features_v3_legacy_feature ON spatial_features_v3 (legacy_spatial_layer_feature_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('spatial_features_v3');
    }
};
