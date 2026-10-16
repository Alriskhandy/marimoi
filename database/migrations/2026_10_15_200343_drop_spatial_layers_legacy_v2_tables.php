<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Retire arsip beku skema v2: `spatial_layers_legacy_v2`,
     * `spatial_layer_metadata_legacy_v2`, `spatial_layer_features_legacy_v2`.
     *
     * Ketiganya hasil rename di cutover_rename_legacy_and_v3_tables_to_final_names
     * dan sudah tidak punya pembaca sejak SpatialFeedback dihapus
     * (drop_spatial_feedbacks_feature). Pengganti kanoniknya `layers` /
     * `layer_metadata` / `spatial_features`.
     *
     * `spatial_layer_feature_interventions` dan `spatial_layer_regions` SENGAJA
     * TIDAK di-drop (keduanya kosong, di luar cakupan); cuma FK-nya ke tabel
     * legacy yang dilepas supaya tabel induknya bisa di-drop.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE IF EXISTS spatial_layer_feature_interventions DROP CONSTRAINT IF EXISTS spatial_layer_feature_interventions_feature_id_eksisting_foreig');
        DB::statement('ALTER TABLE IF EXISTS spatial_layer_feature_interventions DROP CONSTRAINT IF EXISTS spatial_layer_feature_interventions_feature_id_intervensi_forei');
        DB::statement('ALTER TABLE IF EXISTS spatial_layer_regions DROP CONSTRAINT IF EXISTS spatial_layer_regions_spatial_layer_id_foreign');

        Schema::dropIfExists('spatial_layer_features_legacy_v2');
        Schema::dropIfExists('spatial_layer_metadata_legacy_v2');
        Schema::dropIfExists('spatial_layers_legacy_v2');
    }

    /**
     * Memulihkan struktur (kolom, index, FK) persis seperti sebelum di-drop.
     * Isi tabel TIDAK bisa dipulihkan — rollback memulihkan struktur, bukan data.
     */
    public function down(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE spatial_layers_legacy_v2 (
                id bigserial PRIMARY KEY,
                public_id uuid NOT NULL,
                slug varchar(255) NOT NULL,
                name varchar(255) NOT NULL,
                title varchar(255) NOT NULL,
                description text NULL,
                source_type varchar(50) NOT NULL DEFAULT 'feature',
                legacy_category_id bigint NULL,
                map_type_id bigint NULL,
                sector_id bigint NULL,
                owner_user_id bigint NULL,
                owner_opd_id bigint NULL,
                geometry_type varchar(30) NULL,
                srid integer NULL,
                extent geometry NULL,
                center_point geometry NULL,
                min_zoom smallint NULL,
                max_zoom smallint NULL,
                visibility varchar(20) NOT NULL DEFAULT 'private',
                is_active boolean NOT NULL DEFAULT true,
                is_downloadable boolean NOT NULL DEFAULT false,
                parent_id bigint NULL,
                is_group boolean NOT NULL DEFAULT false,
                atribut_schema jsonb NULL,
                published_at timestamp NULL,
                thumbnail_path varchar(255) NULL,
                created_at timestamp NULL,
                updated_at timestamp NULL,
                deleted_at timestamp NULL,
                color varchar(25) NULL,
                is_marker boolean NOT NULL DEFAULT false,
                icon varchar(255) NULL,
                opacity numeric(4, 3) NOT NULL DEFAULT 1,
                CONSTRAINT spatial_layers_public_id_unique UNIQUE (public_id),
                CONSTRAINT spatial_layers_slug_unique UNIQUE (slug),
                CONSTRAINT spatial_layers_legacy_category_id_foreign FOREIGN KEY (legacy_category_id) REFERENCES categories_legacy_v1(id) ON DELETE SET NULL,
                CONSTRAINT spatial_layers_owner_user_id_foreign FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL,
                CONSTRAINT spatial_layers_owner_opd_id_foreign FOREIGN KEY (owner_opd_id) REFERENCES opd(id) ON DELETE SET NULL,
                CONSTRAINT spatial_layers_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES spatial_layers_legacy_v2(id) ON DELETE SET NULL,
                CONSTRAINT spatial_layers_sector_id_foreign FOREIGN KEY (sector_id) REFERENCES sectors(id) ON DELETE SET NULL
            )
        SQL);
        DB::statement('CREATE INDEX spatial_layers_owner_opd_id_is_active_visibility_index ON spatial_layers_legacy_v2 (owner_opd_id, is_active, visibility)');
        DB::statement('CREATE INDEX spatial_layers_map_type_id_sector_id_index ON spatial_layers_legacy_v2 (map_type_id, sector_id)');

        DB::statement(<<<'SQL'
            CREATE TABLE spatial_layer_metadata_legacy_v2 (
                id bigserial PRIMARY KEY,
                spatial_layer_id bigint NOT NULL,
                abstract text NULL,
                source_name varchar(255) NULL,
                source_url text NULL,
                license varchar(255) NULL,
                attribution text NULL,
                contact_name varchar(255) NULL,
                contact_email varchar(255) NULL,
                contact_phone varchar(50) NULL,
                data_reference_date date NULL,
                data_reference_year smallint NULL,
                update_frequency varchar(50) NULL,
                last_verified_at timestamp NULL,
                lineage text NULL,
                positional_accuracy text NULL,
                attribute_accuracy text NULL,
                completeness text NULL,
                limitations text NULL,
                language_code varchar(10) NOT NULL DEFAULT 'id',
                created_by bigint NULL,
                updated_by bigint NULL,
                created_at timestamp NULL,
                updated_at timestamp NULL,
                CONSTRAINT spatial_layer_metadata_spatial_layer_id_unique UNIQUE (spatial_layer_id),
                CONSTRAINT spatial_layer_metadata_spatial_layer_id_foreign FOREIGN KEY (spatial_layer_id) REFERENCES spatial_layers_legacy_v2(id) ON DELETE CASCADE,
                CONSTRAINT spatial_layer_metadata_created_by_foreign FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
                CONSTRAINT spatial_layer_metadata_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE spatial_layer_features_legacy_v2 (
                id bigserial PRIMARY KEY,
                spatial_layer_id bigint NOT NULL,
                source_version_id bigint NULL,
                external_id varchar(255) NULL,
                geometry geometry NOT NULL,
                region_id bigint NULL,
                attributes jsonb NULL,
                created_by bigint NULL,
                legacy_data_spatial_id bigint NULL,
                created_at timestamp NULL,
                updated_at timestamp NULL,
                gambar varchar(255) NULL,
                metadata_dinamis jsonb NULL,
                CONSTRAINT spatial_layer_features_spatial_layer_id_foreign FOREIGN KEY (spatial_layer_id) REFERENCES spatial_layers_legacy_v2(id) ON DELETE RESTRICT,
                CONSTRAINT spatial_layer_features_created_by_foreign FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
                CONSTRAINT spatial_layer_features_legacy_data_spatial_id_foreign FOREIGN KEY (legacy_data_spatial_id) REFERENCES data_spatial_legacy_v1(id) ON DELETE SET NULL,
                CONSTRAINT spatial_layer_features_region_id_foreign FOREIGN KEY (region_id) REFERENCES administrative_regions(id) ON DELETE SET NULL
            )
        SQL);
        DB::statement('CREATE INDEX spatial_layer_features_spatial_layer_id_index ON spatial_layer_features_legacy_v2 (spatial_layer_id)');
        DB::statement('CREATE INDEX spatial_layer_features_legacy_data_spatial_id_index ON spatial_layer_features_legacy_v2 (legacy_data_spatial_id)');
        DB::statement('CREATE INDEX spatial_layer_features_geometry_gist ON spatial_layer_features_legacy_v2 USING gist (geometry)');

        if (Schema::hasTable('spatial_layer_feature_interventions')) {
            DB::statement('ALTER TABLE spatial_layer_feature_interventions ADD CONSTRAINT spatial_layer_feature_interventions_feature_id_eksisting_foreig FOREIGN KEY (feature_id_eksisting) REFERENCES spatial_layer_features_legacy_v2(id) ON DELETE CASCADE');
            DB::statement('ALTER TABLE spatial_layer_feature_interventions ADD CONSTRAINT spatial_layer_feature_interventions_feature_id_intervensi_forei FOREIGN KEY (feature_id_intervensi) REFERENCES spatial_layer_features_legacy_v2(id) ON DELETE CASCADE');
        }

        if (Schema::hasTable('spatial_layer_regions')) {
            DB::statement('ALTER TABLE spatial_layer_regions ADD CONSTRAINT spatial_layer_regions_spatial_layer_id_foreign FOREIGN KEY (spatial_layer_id) REFERENCES spatial_layers_legacy_v2(id) ON DELETE CASCADE');
        }
    }
};
