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
     * region_id (wilayah) belum diberi foreign key di sini — tabel administrative_regions
     * baru dibuat Prioritas 4. Kolomnya sudah disiapkan sekarang supaya tidak perlu
     * migration ALTER TABLE lagi nanti.
     */
    public function up(): void
    {
        Schema::create('spatial_layer_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spatial_layer_id')->constrained('spatial_layers')->restrictOnDelete();
            $table->foreignId('source_version_id')->nullable(); // FK ditambahkan setelah spatial_layer_versions ada
            $table->string('external_id')->nullable();
            $table->geometry('geometry');
            $table->unsignedBigInteger('region_id')->nullable();
            $table->jsonb('attributes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('legacy_data_spatial_id')->nullable()->constrained('data_spatial')->nullOnDelete();
            $table->timestamps();

            $table->index('spatial_layer_id');
            $table->index('legacy_data_spatial_id');
        });

        DB::statement('CREATE INDEX spatial_layer_features_geometry_gist ON spatial_layer_features USING GIST (geometry)');

        // Backfill langsung lewat SQL (bukan lewat Eloquent/PHP) — seluruh proses termasuk
        // penyalinan kolom geometry besar terjadi di sisi database, tidak pernah menarik
        // data ke memori PHP. Ini aman untuk ~11.900+ baris data_spatial, beda dengan
        // pola Eloquent chunk yang berisiko OOM (pernah terjadi di CategoryController/
        // ProjectFeedbackController sebelumnya).
        DB::statement('
            INSERT INTO spatial_layer_features
                (spatial_layer_id, external_id, geometry, attributes, created_by, legacy_data_spatial_id, created_at, updated_at)
            SELECT
                sl.id,
                ds.uuid,
                ds.geom,
                ds.dbf_attributes,
                ds.user_id,
                ds.id,
                ds.created_at,
                ds.updated_at
            FROM data_spatial ds
            JOIN spatial_layers sl ON sl.legacy_category_id = ds.kategori_id
            WHERE ds.kategori_id IS NOT NULL AND ds.geom IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spatial_layer_features');
    }
};
