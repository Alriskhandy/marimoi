<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Skema v3 §5.3 — tabel referensi klasifikasi teknis layer (geometry/data
 * kind), isinya seed tetap. Berbeda dari `map_types` (klasifikasi bisnis
 * "Jenis Peta" yang sudah ada & tetap dipertahankan, lihat plan Keputusan #2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('layer_types', function (Blueprint $table) {
            $table->smallInteger('id')->primary();
            $table->string('code', 40)->unique();
            $table->string('name', 100);
            $table->string('data_kind', 20);
            $table->string('geometry_type', 30)->nullable();
            $table->boolean('stores_features')->default(true);
            $table->text('description')->nullable();
        });

        DB::statement("ALTER TABLE layer_types ADD CONSTRAINT ck_layer_types_data_kind CHECK (data_kind IN ('vector','raster','service'))");
        DB::statement(<<<'SQL'
            ALTER TABLE layer_types ADD CONSTRAINT ck_layer_types_geometry_type CHECK (geometry_type IN
                ('POINT','MULTIPOINT','LINESTRING','MULTILINESTRING','POLYGON','MULTIPOLYGON','GEOMETRY'))
            SQL);

        DB::table('layer_types')->insert([
            ['id' => 1, 'code' => 'vector_point', 'name' => 'Vektor Titik', 'data_kind' => 'vector', 'geometry_type' => 'MULTIPOINT', 'stores_features' => true, 'description' => null],
            ['id' => 2, 'code' => 'vector_line', 'name' => 'Vektor Garis', 'data_kind' => 'vector', 'geometry_type' => 'MULTILINESTRING', 'stores_features' => true, 'description' => null],
            ['id' => 3, 'code' => 'vector_polygon', 'name' => 'Vektor Poligon', 'data_kind' => 'vector', 'geometry_type' => 'MULTIPOLYGON', 'stores_features' => true, 'description' => null],
            ['id' => 4, 'code' => 'vector_mixed', 'name' => 'Vektor Campuran', 'data_kind' => 'vector', 'geometry_type' => 'GEOMETRY', 'stores_features' => true, 'description' => null],
            ['id' => 10, 'code' => 'raster_cog', 'name' => 'Raster (COG/GeoTIFF)', 'data_kind' => 'raster', 'geometry_type' => null, 'stores_features' => false, 'description' => null],
            ['id' => 20, 'code' => 'service_wms', 'name' => 'Layanan WMS', 'data_kind' => 'service', 'geometry_type' => null, 'stores_features' => false, 'description' => null],
            ['id' => 21, 'code' => 'service_wmts', 'name' => 'Layanan WMTS', 'data_kind' => 'service', 'geometry_type' => null, 'stores_features' => false, 'description' => null],
            ['id' => 22, 'code' => 'service_xyz', 'name' => 'Tile XYZ', 'data_kind' => 'service', 'geometry_type' => null, 'stores_features' => false, 'description' => null],
            ['id' => 23, 'code' => 'service_arcgis', 'name' => 'ArcGIS REST', 'data_kind' => 'service', 'geometry_type' => null, 'stores_features' => false, 'description' => null],
            ['id' => 24, 'code' => 'service_vector_tile', 'name' => 'Vector Tile (MVT)', 'data_kind' => 'service', 'geometry_type' => null, 'stores_features' => false, 'description' => null],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('layer_types');
    }
};
