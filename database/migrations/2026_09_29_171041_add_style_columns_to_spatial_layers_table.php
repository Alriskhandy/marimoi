<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Bagian 1.3 docs/marimoi v2/04_implementation/12-implementasi-perbaikan-pemetaan.md.
     * color/is_marker/parent_id sudah ada sejak Prioritas 1 — cuma icon & opacity
     * yang kurang untuk memenuhi "Layer: konfigurasi style (warna, ikon, opacity)".
     */
    public function up(): void
    {
        Schema::table('spatial_layers', function (Blueprint $table) {
            $table->string('icon')->nullable()->after('color');
            $table->decimal('opacity', 4, 3)->default(1)->after('icon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spatial_layers', function (Blueprint $table) {
            $table->dropColumn(['icon', 'opacity']);
        });
    }
};
