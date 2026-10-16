<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom jejak migrasi (Fase 2) — terlewat saat migration awal dibuat.
 * Dibutuhkan command marimoi:migrate-schema-v3 untuk mencari cepat node v3
 * mana yang berasal dari categories.id lama, sama seperti pola
 * legacy_spatial_layer_id/legacy_category_id di layers_v3. Dihapus bersama
 * kolom legacy lain setelah skema v3 stabil di produksi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories_v3', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_category_id')->nullable()->index();
        });

        Schema::table('category_nodes', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_category_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('categories_v3', function (Blueprint $table) {
            $table->dropColumn('legacy_category_id');
        });

        Schema::table('category_nodes', function (Blueprint $table) {
            $table->dropColumn('legacy_category_id');
        });
    }
};
