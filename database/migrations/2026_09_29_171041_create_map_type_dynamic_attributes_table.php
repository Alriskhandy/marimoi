<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Bagian 1.2 docs/marimoi v2/04_implementation/12-implementasi-perbaikan-pemetaan.md.
     * Menyimpan DEFINISI/skema atribut dinamis per Jenis, bukan nilainya — nilai
     * selalu diisi di Data Spasial (spatial_layer_features.metadata_dinamis) saat
     * dibuat. Jenis hanya referensi/panduan (Keputusan #2), tidak simpan nilai.
     */
    public function up(): void
    {
        Schema::create('map_type_dynamic_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('map_type_id')->constrained('map_types')->cascadeOnDelete();
            $table->string('tipe', 20); // 'placeholder' | 'custom'
            $table->string('kode_atribut'); // key di metadata_dinamis jsonb
            $table->string('label');
            $table->string('satuan')->nullable();
            $table->boolean('is_wajib')->default(false);
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['map_type_id', 'kode_atribut']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('map_type_dynamic_attributes');
    }
};
