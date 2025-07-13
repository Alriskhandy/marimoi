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
        Schema::create('pokir_dprds', function (Blueprint $table) {
            $table->id();
        $table->foreignId('kategori_id')->constrained('kategori_pokir_dprds')->onDelete('restrict');
        $table->text('deskripsi')->nullable();
        $table->jsonb('dbf_attributes')->nullable();
        $table->timestamps();

        $table->index('kategori_id');
        $table->index('dbf_attributes', null, 'gin');
        });

         // Tambahkan kolom geometri menggunakan PostGIS dengan support untuk Z dan M dimensions

        // GEOMETRYZM mendukung X, Y, Z (elevation), dan M (measure) coordinates 3D
        // DB::statement('ALTER TABLE pokir_dprds ADD COLUMN geom GEOMETRY(GEOMETRYZM, 4326)');

        // Jika tidak ingin menggunakan Z dan M dimensions, gunakan ini: 2D
        // DB::statement('ALTER TABLE pokir_dprds ADD COLUMN geom GEOMETRY(Geometry, 4326)');
        // tidak keduanya
        DB::statement('ALTER TABLE pokir_dprds ADD COLUMN geom GEOMETRY');

        // Buat spatial index untuk geometri
        DB::statement('CREATE INDEX idx_pokir_dprds_geom ON pokir_dprds USING GIST (geom)');
        
        // Buat index untuk pencarian teks dalam JSONB
        DB::statement('CREATE INDEX idx_pokir_dprds_dbf_gin ON pokir_dprds USING GIN (dbf_attributes jsonb_path_ops)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pokir_dprds');
    }
};
