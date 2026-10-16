<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kembalikan nama tabel kompatibilitas v1 ke nama aslinya, sama dengan
     * database versi lama, supaya tidak membingungkan saat membandingkan
     * atau memindahkan data (lihat command marimoi:import-legacy).
     *
     * Kedua tabel ini di-rename jadi *_legacy_v1 oleh
     * cutover_rename_legacy_and_v3_tables_to_final_names. Nama `categories`
     * dan `data_spatial` tidak dipakai tabel lain di skema V3 (kategori V3 ada
     * di `categories_v3`/`category_nodes`, fitur di `spatial_features`).
     */
    public function up(): void
    {
        Schema::rename('categories_legacy_v1', 'categories');
        Schema::rename('data_spatial_legacy_v1', 'data_spatial');
    }

    public function down(): void
    {
        Schema::rename('data_spatial', 'data_spatial_legacy_v1');
        Schema::rename('categories', 'categories_legacy_v1');
    }
};
