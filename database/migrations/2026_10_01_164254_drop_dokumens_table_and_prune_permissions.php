<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fitur "Upload Dokumen" (DokumenController/Dokumen model) dihapus total —
 * tabel `dokumens` dan permission `dokumen.*` (termasuk yang sudah ter-assign
 * ke role) ikut dibersihkan supaya tidak ada sisa data orphan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('dokumens');

        DB::table('permissions')->where('name', 'like', 'dokumen.%')->delete();
    }

    public function down(): void
    {
        Schema::create('dokumens', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('file');
            $table->timestamps();
        });
    }
};
