<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tidak ada backfill di sini — data wilayah (administrative_regions) belum diisi
     * (Prioritas 4 masih kerangka, menunggu sumber data resmi), jadi tabel ini mulai
     * kosong dan terisi natural begitu wilayah tersedia.
     */
    public function up(): void
    {
        Schema::create('project_regions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('development_project_id')->constrained('development_projects')->cascadeOnDelete();
            $table->foreignId('region_id')->constrained('administrative_regions')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['development_project_id', 'region_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_regions');
    }
};
