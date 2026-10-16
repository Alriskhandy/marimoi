<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Sumber data resmi wilayah administratif (Keputusan #3 di 09-implementasi-penuh-
     * database-v2.md) sudah tersedia (docs/marimoi v2/Kode_Wilayah_Provinsi_Maluku_Utara.md),
     * lengkap dengan DUA versi kode berbeda per wilayah (BPS dan Kemendagri) — keduanya
     * dipakai luas di dokumen pemerintah/statistik, jadi disimpan sebagai kolom terpisah
     * daripada memaksa satu kolom `code` menjadi salah satunya saja.
     */
    public function up(): void
    {
        Schema::table('administrative_regions', function (Blueprint $table) {
            $table->renameColumn('code', 'code_kemendagri');
        });

        Schema::table('administrative_regions', function (Blueprint $table) {
            $table->string('code_bps', 20)->nullable()->unique()->after('code_kemendagri');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('administrative_regions', function (Blueprint $table) {
            $table->dropColumn('code_bps');
        });

        Schema::table('administrative_regions', function (Blueprint $table) {
            $table->renameColumn('code_kemendagri', 'code');
        });
    }
};
