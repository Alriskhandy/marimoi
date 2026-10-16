<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `sumber_data` (mis. nama instansi/dokumen asal data) dipisah dari
 * `producer_organization` ("Organisasi Produsen Data") yang sudah ada —
 * field itu khusus organisasi penanggung jawab, bukan deskripsi bebas asal
 * data yang diisi Admin OPD sejak tahap 1 wizard Tambah Layer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('layer_metadata', function (Blueprint $table) {
            $table->string('sumber_data', 255)->nullable()->after('producer_organization');
        });
    }

    public function down(): void
    {
        Schema::table('layer_metadata', function (Blueprint $table) {
            $table->dropColumn('sumber_data');
        });
    }
};
