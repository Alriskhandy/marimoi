<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Bagian 6 Tahap 2 (14-penyesuaian-database-jenis-peta.md) — nullable dulu,
     * diisi lewat command `marimoi:migrate-metadata-definitions` (Tahap 3) sebelum
     * di-NOT NULL dan kolom lama (tipe/kode_atribut/label/satuan) di-drop di
     * migration terpisah (Tahap 4), setelah backfill diverifikasi tuntas.
     */
    public function up(): void
    {
        Schema::table('map_type_dynamic_attributes', function (Blueprint $table) {
            $table->foreignId('metadata_definition_id')->nullable()->after('map_type_id')
                ->constrained('metadata_definitions')->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true)->after('metadata_definition_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('map_type_dynamic_attributes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('metadata_definition_id');
            $table->dropColumn('is_enabled');
        });
    }
};
