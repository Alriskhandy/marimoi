<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kop baris 3 & teks footer kiri boleh beberapa baris (teks panjang). Footer kanan kini
 * otomatis (basemap, sistem koordinat, tanggal & waktu cetak), jadi kolom catatan kanan
 * dan sakelar tanggal cetak dihapus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_templates', function (Blueprint $table) {
            $table->text('header_line3')->nullable()->change();
            $table->text('footer_text')->nullable()->change();
            $table->dropColumn(['footer_note', 'show_print_date']);
        });
    }

    public function down(): void
    {
        Schema::table('document_templates', function (Blueprint $table) {
            $table->string('header_line3')->nullable()->change();
            $table->string('footer_text')->nullable()->change();
            $table->string('footer_note')->nullable()->after('footer_text');
            $table->boolean('show_print_date')->default(true)->after('footer_note');
        });
    }
};
