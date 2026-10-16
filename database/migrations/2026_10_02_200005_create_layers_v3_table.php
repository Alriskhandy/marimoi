<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Skema v3 §5.4, nama sementara `layers_v3` (lihat catatan di
 * create_categories_v3_table.php). PK `id` di-generate baru lewat
 * gen_random_uuid() — SENGAJA bukan reuse `spatial_layers.public_id` lama,
 * supaya id publik v2 dan v3 tidak tercampur (lihat plan §2 catatan PK).
 *
 * Deviasi dari dokumen (plan Keputusan #2): kolom `map_type_id` ditambahkan,
 * FK ke `map_types` yang sudah ada — supaya sistem atribut dinamis per Jenis
 * Peta (map_type_dynamic_attributes + metadata_definitions) tetap berfungsi
 * tanpa redesain ke per-layer.
 *
 * Kolom `legacy_spatial_layer_id`/`legacy_category_id` murni jejak migrasi
 * data (Fase 2), dihapus di migration terpisah setelah skema v3 stabil di
 * produksi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('layers_v3', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('category_id');
            $table->uuid('category_node_id')->nullable();
            $table->smallInteger('layer_type_id');
            $table->foreignId('map_type_id')->nullable()->constrained('map_types')->nullOnDelete();
            $table->string('code', 80);
            $table->string('name', 200);
            $table->string('slug', 220);
            $table->string('short_description', 500)->nullable();
            $table->string('geometry_type')->nullable();
            $table->integer('storage_srid')->default(4326);
            $table->geometry('bbox', subtype: 'polygon', srid: 4326)->nullable();
            $table->integer('feature_count')->default(0);
            $table->uuid('default_style_id')->nullable();
            $table->string('visibility', 20)->default('public');
            $table->string('status', 20)->default('draft');
            $table->boolean('is_default_on')->default(false);
            $table->boolean('is_downloadable')->default(false);
            $table->boolean('is_queryable')->default(true);
            $table->smallInteger('min_zoom')->nullable();
            $table->smallInteger('max_zoom')->nullable();
            $table->decimal('default_opacity', 3, 2)->default(1.00);
            $table->integer('sort_order')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unsignedBigInteger('legacy_spatial_layer_id')->nullable();
            $table->unsignedBigInteger('legacy_category_id')->nullable();

            $table->foreign('category_id')->references('id')->on('categories_v3');
            $table->unique(['id', 'category_id'], 'uq_layers_v3_id_category');
        });

        DB::statement(<<<'SQL'
            ALTER TABLE layers_v3 ADD CONSTRAINT fk_layers_v3_category_node
                FOREIGN KEY (category_node_id, category_id)
                REFERENCES category_nodes (id, category_id)
            SQL);
        DB::statement('ALTER TABLE layers_v3 ADD CONSTRAINT fk_layers_v3_layer_type FOREIGN KEY (layer_type_id) REFERENCES layer_types (id)');
        DB::statement("ALTER TABLE layers_v3 ADD CONSTRAINT ck_layers_v3_visibility CHECK (visibility IN ('public','internal','private'))");
        DB::statement("ALTER TABLE layers_v3 ADD CONSTRAINT ck_layers_v3_status CHECK (status IN ('draft','published','archived'))");
        DB::statement('ALTER TABLE layers_v3 ADD CONSTRAINT ck_layers_v3_zoom CHECK (min_zoom IS NULL OR max_zoom IS NULL OR min_zoom <= max_zoom)');
        DB::statement("ALTER TABLE layers_v3 ADD CONSTRAINT ck_layers_v3_published CHECK (status <> 'published' OR published_at IS NOT NULL)");
        DB::statement('ALTER TABLE layers_v3 ADD CONSTRAINT ck_layers_v3_opacity CHECK (default_opacity BETWEEN 0 AND 1)');

        DB::statement('CREATE UNIQUE INDEX uq_layers_v3_code ON layers_v3 (code) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX uq_layers_v3_slug ON layers_v3 (slug) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX ix_layers_v3_category ON layers_v3 (category_id, category_node_id, sort_order)');
        DB::statement('CREATE INDEX ix_layers_v3_status_vis ON layers_v3 (status, visibility) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX ix_layers_v3_bbox ON layers_v3 USING gist (bbox)');
        DB::statement('CREATE INDEX ix_layers_v3_name_trgm ON layers_v3 USING gin (name gin_trgm_ops)');
        DB::statement('CREATE INDEX ix_layers_v3_legacy_spatial_layer ON layers_v3 (legacy_spatial_layer_id)');
        DB::statement('CREATE INDEX ix_layers_v3_legacy_category ON layers_v3 (legacy_category_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('layers_v3');
    }
};
