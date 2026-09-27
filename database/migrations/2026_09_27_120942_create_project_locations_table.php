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
        Schema::create('project_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('development_project_id')->constrained('development_projects')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('geometry_type', 30);
            $table->geometry('geometry');
            $table->foreignId('region_id')->nullable()->constrained('administrative_regions')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        DB::statement('CREATE INDEX project_locations_geometry_gist ON project_locations USING GIST (geometry)');

        // Backfill lokasi dari spatial_layer_features (sumber geometry kanonik sekarang),
        // dihubungkan lewat legacy_data_spatial_id yang sama-sama merujuk data_spatial asli.
        // geometry_type diambil dari ST_GeometryType, bukan ditebak/di-hardcode.
        DB::statement('
            INSERT INTO project_locations (development_project_id, geometry_type, geometry, created_at, updated_at)
            SELECT
                dp.id,
                ST_GeometryType(slf.geometry),
                slf.geometry,
                now(),
                now()
            FROM development_projects dp
            JOIN spatial_layer_features slf ON slf.legacy_data_spatial_id = dp.legacy_data_spatial_id
            WHERE dp.legacy_data_spatial_id IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_locations');
    }
};
