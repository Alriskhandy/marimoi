<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `layer_class` (thematic/development) dimaksudkan jadi klasifikasi stabil
     * lintas fitur (dashboard, routing, policy — lihat docs/marimoi v2/
     * db-schema-v2.md), tapi tidak pernah benar-benar dipakai jadi logika apa pun
     * di controller manapun — murni kolom wajib diisi tanpa konsumen. Dihapus atas
     * permintaan user.
     */
    public function up(): void
    {
        Schema::table('spatial_layers', function (Blueprint $table) {
            $table->dropColumn('layer_class');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spatial_layers', function (Blueprint $table) {
            $table->string('layer_class', 30)->nullable()->after('description');
        });
    }
};
