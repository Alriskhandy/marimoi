<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Skema v3 (docs/marimoi v2/db-schema-v3.md §5.1), dibuat dengan nama
 * sementara `categories_v3` karena nama final `categories` masih dipakai
 * tabel lama (bigint PK) sampai migration rename di Fase 6.
 *
 * Deviasi dari dokumen: kolom `type` ditambahkan (lihat plan Keputusan #3) —
 * kolom ini dipakai sebagai proxy `map_types.slug` di validasi/filter/cache
 * key pada CategoryController, DataSpatialController, FrontendController,
 * dan PembangunanDashboardController pada skema lama; membuangnya akan
 * merusak seluruh filter tersebut.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories_v3', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->string('code', 50);
            $table->string('name', 150);
            $table->string('slug', 160);
            $table->string('type', 60)->nullable();
            $table->text('description')->nullable();
            $table->string('icon', 100)->nullable();
            $table->string('color', 9)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('CREATE UNIQUE INDEX uq_categories_v3_code ON categories_v3 (code) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX uq_categories_v3_slug ON categories_v3 (slug) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX ix_categories_v3_type ON categories_v3 (type) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('categories_v3');
    }
};
