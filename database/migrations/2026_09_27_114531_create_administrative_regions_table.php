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
     * Level awal: provinsi + kabupaten/kota + kecamatan (cukup untuk kebutuhan filter
     * dashboard). Desa/kelurahan bisa ditambah kemudian tanpa migrasi ulang, karena
     * skema (level + parent_id) sudah mendukungnya sejak awal — hanya belum diisi.
     *
     * Tidak ada check constraint DB untuk kolom `level` (mengikuti konvensi
     * project_progress_reports.status yang divalidasi di aplikasi) supaya menambah
     * level baru nanti tidak perlu migration baru.
     */
    public function up(): void
    {
        Schema::create('administrative_regions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Kode wilayah resmi Kemendagri');
            $table->string('name');
            $table->string('level', 30); // provinsi | kabupaten_kota | kecamatan
            $table->foreignId('parent_id')->nullable()->constrained('administrative_regions')->nullOnDelete();
            $table->geometry('geometry')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('level');
        });

        DB::statement('CREATE INDEX administrative_regions_geometry_gist ON administrative_regions USING GIST (geometry)');

        Schema::table('spatial_layer_features', function (Blueprint $table) {
            $table->foreign('region_id')->references('id')->on('administrative_regions')->nullOnDelete();
        });

        Schema::create('spatial_layer_regions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spatial_layer_id')->constrained('spatial_layers')->cascadeOnDelete();
            $table->foreignId('region_id')->constrained('administrative_regions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['spatial_layer_id', 'region_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spatial_layer_regions');

        Schema::table('spatial_layer_features', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
        });

        Schema::dropIfExists('administrative_regions');
    }
};
