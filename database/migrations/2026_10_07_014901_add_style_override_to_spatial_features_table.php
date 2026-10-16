<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengaturan style satu Layer (layer_styles.definition) berlaku untuk SEMUA
 * Data Spasial miliknya — kolom ini memungkinkan satu Data Spasial dikustom
 * sendiri (warna/ukuran/opacity/marker berbeda dari Layer-nya), lihat
 * SpatialLayerFeatureController::validated() & SpatialMapController::geojson().
 * NULL = ikut style default Layer (perilaku lama, tidak berubah).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spatial_features', function (Blueprint $table) {
            $table->jsonb('style_override')->nullable()->after('properties');
        });
    }

    public function down(): void
    {
        Schema::table('spatial_features', function (Blueprint $table) {
            $table->dropColumn('style_override');
        });
    }
};
