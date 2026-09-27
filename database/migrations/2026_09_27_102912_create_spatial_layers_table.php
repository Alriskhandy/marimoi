<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('spatial_layers', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('layer_class', 30);
            $table->string('source_type', 50)->default('feature');
            $table->foreignId('legacy_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('map_type_id')->nullable()->constrained('map_types')->nullOnDelete();
            $table->unsignedBigInteger('sector_id')->nullable();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('owner_opd_id')->nullable()->constrained('opd')->nullOnDelete();
            $table->string('geometry_type', 30)->nullable();
            $table->integer('srid')->nullable();
            $table->geometry('extent')->nullable();
            $table->geometry('center_point')->nullable();
            $table->smallInteger('min_zoom')->nullable();
            $table->smallInteger('max_zoom')->nullable();
            $table->string('visibility', 20)->default('private');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_downloadable')->default(false);
            $table->foreignId('parent_id')->nullable()->constrained('spatial_layers')->nullOnDelete();
            $table->boolean('is_group')->default(false);
            $table->jsonb('atribut_schema')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_opd_id', 'is_active', 'visibility']);
            $table->index(['map_type_id', 'sector_id']);
        });

        // Backfill identitas dari categories. map_type_id/is_group/atribut_schema TIDAK
        // di-backfill dari categories (kolom itu tidak ada di categories saat ini — lihat
        // docs/marimoi v2/04_implementation/09-implementasi-penuh-database-v2.md Prioritas 1),
        // dibuat kosong/default lalu diisi lewat CRUD map_types/Layer setelah migration ini.
        DB::statement("
            INSERT INTO spatial_layers
                (public_id, slug, name, title, description, layer_class, map_type_id,
                 owner_user_id, visibility, is_active, is_group,
                 legacy_category_id, created_at, updated_at)
            SELECT
                gen_random_uuid(),
                'layer-' || c.id || '-' || regexp_replace(lower(c.nama), '[^a-z0-9]+', '-', 'g'),
                c.nama,
                c.nama,
                c.deskripsi,
                CASE WHEN c.type = 'tematik' THEN 'thematic' ELSE 'development' END,
                mt.id,
                c.user_id,
                'private',
                c.is_active,
                false,
                c.id,
                c.created_at,
                c.updated_at
            FROM categories c
            LEFT JOIN map_types mt ON mt.slug = c.type
        ");

        // Pass kedua: isi parent_id spatial_layers dari parent_id categories lewat legacy_category_id.
        DB::statement('
            UPDATE spatial_layers sl
            SET parent_id = parent_sl.id
            FROM categories c
            JOIN spatial_layers parent_sl ON parent_sl.legacy_category_id = c.parent_id
            WHERE sl.legacy_category_id = c.id AND c.parent_id IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spatial_layers');
    }
};
