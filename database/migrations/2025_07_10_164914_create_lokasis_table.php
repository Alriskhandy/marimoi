<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
      public function up(): void
    {
        // Pastikan ekstensi PostGIS sudah aktif
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
        
        Schema::create('lokasis', function (Blueprint $table) {
            $table->id();
            $table->string('kategori');
            $table->text('deskripsi')->nullable();
            
            // Kolom untuk menyimpan semua atribut DBF sebagai JSONB (lebih efisien di PostgreSQL)
            $table->jsonb('dbf_attributes')->nullable();
            
            $table->timestamps();
            
            // Index untuk pencarian berdasarkan kategori
            $table->index('kategori');
            
            // Index untuk pencarian dalam JSONB
            $table->index('dbf_attributes', null, 'gin');
        });
        
        // Tambahkan kolom geometri menggunakan PostGIS dengan support untuk Z dan M dimensions
        // GEOMETRYZM mendukung X, Y, Z (elevation), dan M (measure) coordinates
        DB::statement('ALTER TABLE lokasis ADD COLUMN geom GEOMETRY(GEOMETRYZM, 4326)');
        // DB::statement('ALTER TABLE lokasis ADD COLUMN geom GEOMETRY(Geometry, 4326)');

        // Buat spatial index untuk geometri
        DB::statement('CREATE INDEX idx_lokasis_geom ON lokasis USING GIST (geom)');
        
        // Buat index untuk pencarian teks dalam JSONB
        DB::statement('CREATE INDEX idx_lokasis_dbf_gin ON lokasis USING GIN (dbf_attributes jsonb_path_ops)');
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lokasis');
    }
};
