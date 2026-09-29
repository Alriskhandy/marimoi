<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Bagian 1.4 docs/marimoi v2/04_implementation/12-implementasi-perbaikan-pemetaan.md.
     * Dua kolom terpisah dengan tanggung jawab beda (Keputusan #2): `attributes`
     * (sudah ada) tetap untuk atribut mentah hasil impor SHP/KMZ/KML apa adanya;
     * `metadata_dinamis` (baru) khusus nilai terstruktur sesuai skema Jenis
     * (map_type_dynamic_attributes) — jangan dicampur.
     */
    public function up(): void
    {
        Schema::table('spatial_layer_features', function (Blueprint $table) {
            $table->string('gambar')->nullable()->after('attributes');
            $table->jsonb('metadata_dinamis')->nullable()->after('gambar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spatial_layer_features', function (Blueprint $table) {
            $table->dropColumn(['gambar', 'metadata_dinamis']);
        });
    }
};
