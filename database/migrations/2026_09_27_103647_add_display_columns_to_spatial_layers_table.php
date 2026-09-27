<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menutup gap yang ditemukan saat verifikasi Prioritas 1 (lihat
     * docs/marimoi v2/04_implementation/09-implementasi-penuh-database-v2.md):
     * migration awal spatial_layers tidak menyertakan `color`/`is_marker`, padahal
     * kedua field ini masih aktif dipakai admin category views (legend warna, marker
     * rendering) — bukan kolom kosmetik yang aman diabaikan.
     */
    public function up(): void
    {
        Schema::table('spatial_layers', function (Blueprint $table) {
            $table->string('color', 25)->nullable()->after('geometry_type');
            $table->boolean('is_marker')->default(false)->after('color');
        });

        DB::statement('
            UPDATE spatial_layers sl
            SET color = c.warna,
                is_marker = c.is_marker,
                thumbnail_path = c.gambar
            FROM categories c
            WHERE sl.legacy_category_id = c.id
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spatial_layers', function (Blueprint $table) {
            $table->dropColumn(['color', 'is_marker']);
        });
    }
};
