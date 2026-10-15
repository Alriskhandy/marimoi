<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop tabel yang modelnya ada tapi relasinya tidak pernah dipanggil
     * controller/view/command mana pun (dead code):
     *
     * - `project_locations` — DevelopmentProject::locations() (121 baris hasil
     *   backfill Prioritas 5, tidak pernah dibaca).
     * - `user_identities` — tidak terpakai sejak login Google dihapus.
     * - `user_role_assignments` — User::roleAssignments().
     * - `project_progress_report_revisions` — ProjectProgressReport::revisions().
     * - `spatial_layer_regions` — AdministrativeRegion::spatialLayers(); FK
     *   ke layer sudah lepas di drop_spatial_layers_legacy_v2_tables.
     * - `spatial_layer_feature_interventions` — relasinya sudah dilepas sejak
     *   skema v3; FK ke fitur sudah lepas di drop_spatial_layers_legacy_v2_tables.
     */
    public function up(): void
    {
        Schema::dropIfExists('project_locations');
        Schema::dropIfExists('user_identities');
        Schema::dropIfExists('user_role_assignments');
        Schema::dropIfExists('project_progress_report_revisions');
        Schema::dropIfExists('spatial_layer_regions');
        Schema::dropIfExists('spatial_layer_feature_interventions');
    }

    /**
     * Memulihkan struktur (kolom, index, FK) persis seperti sebelum di-drop.
     * Isi tabel TIDAK bisa dipulihkan — rollback memulihkan struktur, bukan data.
     */
    public function down(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE project_locations (
                id bigserial PRIMARY KEY,
                development_project_id bigint NOT NULL,
                name varchar(255) NULL,
                geometry_type varchar(30) NOT NULL,
                geometry geometry NOT NULL,
                region_id bigint NULL,
                notes text NULL,
                created_at timestamp NULL,
                updated_at timestamp NULL,
                CONSTRAINT project_locations_development_project_id_foreign FOREIGN KEY (development_project_id) REFERENCES development_projects(id) ON DELETE CASCADE,
                CONSTRAINT project_locations_region_id_foreign FOREIGN KEY (region_id) REFERENCES administrative_regions(id) ON DELETE SET NULL
            )
        SQL);
        DB::statement('CREATE INDEX project_locations_geometry_gist ON project_locations USING gist (geometry)');

        DB::statement(<<<'SQL'
            CREATE TABLE user_identities (
                id bigserial PRIMARY KEY,
                user_id bigint NOT NULL,
                provider varchar(50) NOT NULL,
                provider_subject varchar(255) NOT NULL,
                provider_email varchar(255) NULL,
                provider_data jsonb NULL,
                last_used_at timestamp NULL,
                created_at timestamp NULL,
                updated_at timestamp NULL,
                CONSTRAINT user_identities_provider_provider_subject_unique UNIQUE (provider, provider_subject),
                CONSTRAINT user_identities_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        SQL);
        DB::statement('CREATE INDEX user_identities_user_id_index ON user_identities (user_id)');

        DB::statement(<<<'SQL'
            CREATE TABLE user_role_assignments (
                id bigserial PRIMARY KEY,
                user_id bigint NOT NULL,
                role_id bigint NOT NULL,
                opd_id bigint NULL,
                assigned_by bigint NULL,
                started_at timestamp NOT NULL,
                ended_at timestamp NULL,
                reason text NULL,
                created_at timestamp NULL,
                updated_at timestamp NULL,
                CONSTRAINT user_role_assignments_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT user_role_assignments_role_id_foreign FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT,
                CONSTRAINT user_role_assignments_opd_id_foreign FOREIGN KEY (opd_id) REFERENCES opd(id) ON DELETE SET NULL,
                CONSTRAINT user_role_assignments_assigned_by_foreign FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
            )
        SQL);
        DB::statement('CREATE INDEX user_role_assignments_user_id_started_at_index ON user_role_assignments (user_id, started_at)');

        DB::statement(<<<'SQL'
            CREATE TABLE project_progress_report_revisions (
                id bigserial PRIMARY KEY,
                project_progress_report_id bigint NOT NULL,
                data_sebelumnya json NOT NULL,
                diperbarui_oleh bigint NOT NULL,
                created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT project_progress_report_revisions_project_progress_report_id_fo FOREIGN KEY (project_progress_report_id) REFERENCES project_progress_reports(id) ON DELETE CASCADE,
                CONSTRAINT project_progress_report_revisions_diperbarui_oleh_foreign FOREIGN KEY (diperbarui_oleh) REFERENCES users(id) ON DELETE RESTRICT
            )
        SQL);
        DB::statement('CREATE INDEX project_progress_report_revisions_project_progress_report_id_in ON project_progress_report_revisions (project_progress_report_id)');

        DB::statement(<<<'SQL'
            CREATE TABLE spatial_layer_regions (
                id bigserial PRIMARY KEY,
                spatial_layer_id bigint NOT NULL,
                region_id bigint NOT NULL,
                created_at timestamp NULL,
                updated_at timestamp NULL,
                CONSTRAINT spatial_layer_regions_spatial_layer_id_region_id_unique UNIQUE (spatial_layer_id, region_id),
                CONSTRAINT spatial_layer_regions_region_id_foreign FOREIGN KEY (region_id) REFERENCES administrative_regions(id) ON DELETE CASCADE
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE spatial_layer_feature_interventions (
                id bigserial PRIMARY KEY,
                feature_id_eksisting bigint NOT NULL,
                feature_id_intervensi bigint NOT NULL,
                jenis_hubungan varchar(50) NOT NULL DEFAULT 'terkait',
                created_by bigint NULL,
                created_at timestamp NULL,
                updated_at timestamp NULL,
                CONSTRAINT slf_interventions_unique_pair UNIQUE (feature_id_eksisting, feature_id_intervensi),
                CONSTRAINT spatial_layer_feature_interventions_created_by_foreign FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
            )
        SQL);
    }
};
