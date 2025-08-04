<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Pastikan ekstensi PostGIS sudah aktif
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
        
        // Tabel categories untuk semua jenis kategori
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'layers', 'musrenbangs', 'pokir_dprds', 'psd', 'psn'
            $table->string('nama', 255)->nullable();
            $table->string('warna', 25)->nullable();
            $table->string('icon', 255)->nullable();
            $table->boolean('is_marker')->default(false);
            $table->text('deskripsi')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->timestamps();
            
            $table->foreign('parent_id')->references('id')->on('categories');
            $table->index(['type', 'parent_id']);
            $table->index('type');
        });

        // Tabel data_spatial untuk semua entitas spatial
        Schema::create('data_spatial', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique()->nullable();
            // Kolom untuk identifikasi jenis data
            $table->string('data_type'); // 'lokasi', 'usulan_musenbang', 'pokir_dprd', 'proyek_strategis'
            $table->string('sub_type')->nullable(); // untuk proyek strategis: 'daerah', 'nasional'
             $table->string('gambar')->nullable();
            // Kolom umum
            $table->unsignedBigInteger('kategori_id')->nullable();
            $table->text('deskripsi')->nullable();
            $table->jsonb('dbf_attributes')->nullable();
            
            // Kolom khusus
            $table->integer('tahun')->nullable(); // untuk proyek strategis dan data yang butuh tahun
            $table->integer('views')->default(0);
            
            $table->timestamps();
            
            // Foreign key ke categories
            $table->foreign('kategori_id')->references('id')->on('categories');
            
            // Indexes untuk performance
            $table->index(['data_type', 'sub_type']);
            $table->index(['data_type', 'kategori_id']);
            $table->index(['data_type', 'tahun']);
            $table->index(['sub_type', 'tahun']); // khusus untuk proyek strategis
            $table->index('kategori_id');
            $table->index('dbf_attributes', null, 'gin');
        });
        
        // Tambahkan kolom geometri menggunakan PostGIS
        DB::statement('ALTER TABLE data_spatial ADD COLUMN geom GEOMETRY');

        // Buat spatial index untuk geometri
        DB::statement('CREATE INDEX idx_data_spatial_geom ON data_spatial USING GIST (geom)');
        
        // Buat index untuk pencarian teks dalam JSONB
        DB::statement('CREATE INDEX idx_data_spatial_dbf_gin ON data_spatial USING GIN (dbf_attributes jsonb_path_ops)');
    }

    public function down(): void
    {
        Schema::dropIfExists('data_spatial');
        Schema::dropIfExists('categories');
    }
};