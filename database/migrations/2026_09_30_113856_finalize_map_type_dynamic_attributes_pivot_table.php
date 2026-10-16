<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Bagian 6 Tahap 4 (14-penyesuaian-database-jenis-peta.md, Opsi B) — dijalankan
     * SETELAH `marimoi:migrate-metadata-definitions` (Tahap 3) diverifikasi tidak
     * menyisakan baris `metadata_definition_id IS NULL`. `map_type_dynamic_attributes`
     * jadi pivot murni ke `metadata_definitions`, kolom `tipe/kode_atribut/label/satuan`
     * lama (definisi yang sekarang tinggal di `metadata_definitions`) dihapus.
     */
    public function up(): void
    {
        Schema::table('map_type_dynamic_attributes', function (Blueprint $table) {
            $table->dropUnique(['map_type_id', 'kode_atribut']);
            $table->dropColumn(['tipe', 'kode_atribut', 'label', 'satuan']);
            $table->foreignId('metadata_definition_id')->nullable(false)->change();
            $table->unique(['map_type_id', 'metadata_definition_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('map_type_dynamic_attributes', function (Blueprint $table) {
            $table->dropUnique(['map_type_id', 'metadata_definition_id']);
            $table->foreignId('metadata_definition_id')->nullable()->change();
            $table->string('tipe', 20)->default('custom')->after('map_type_id');
            $table->string('kode_atribut')->nullable()->after('tipe');
            $table->string('label')->nullable()->after('kode_atribut');
            $table->string('satuan')->nullable()->after('label');
            $table->unique(['map_type_id', 'kode_atribut']);
        });
    }
};
