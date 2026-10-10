<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Orientasi halaman (potret/lanskap) dan tata letak elemen Unduh Peta per template:
 * posisi & ukuran peta, legenda, inset, skala, dan arah mata angin (lihat DocumentTemplate::defaultLayout()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_templates', function (Blueprint $table) {
            $table->string('orientation', 10)->default('landscape')->after('accent_color');
            $table->json('layout')->nullable()->after('orientation');
        });
    }

    public function down(): void
    {
        Schema::table('document_templates', function (Blueprint $table) {
            $table->dropColumn(['orientation', 'layout']);
        });
    }
};
