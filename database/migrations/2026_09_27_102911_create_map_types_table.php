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
        Schema::create('map_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->jsonb('konfigurasi')->nullable();
            $table->timestamps();
        });

        // Seed dari nilai categories.type yang benar-benar dipakai saat ini (lihat
        // docs/marimoi v2/04_implementation/09-implementasi-penuh-database-v2.md
        // "Kondisi Existing yang Diverifikasi" — hasil query nyata, bukan asumsi).
        DB::table('map_types')->insert([
            ['slug' => 'tematik', 'nama' => 'Peta Tematik', 'urutan' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'usulan_musrenbang', 'nama' => 'Usulan Musrenbang', 'urutan' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'pokir_dprd', 'nama' => 'Pokok Pikiran DPRD', 'urutan' => 3, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'psd', 'nama' => 'Proyek Strategis Daerah', 'urutan' => 4, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'psn', 'nama' => 'Proyek Strategis Nasional', 'urutan' => 5, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('map_types');
    }
};
