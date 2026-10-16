<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Bagian 1.1 docs/marimoi v2/04_implementation/12-implementasi-perbaikan-pemetaan.md.
     * Nullable di level database (bukan NOT NULL) supaya baris map_types lama tidak
     * pecah — "wajib" di desain berarti wajib di validator form, bukan constraint DB.
     */
    public function up(): void
    {
        Schema::table('map_types', function (Blueprint $table) {
            $table->string('sumber_data')->nullable()->after('deskripsi');
            $table->foreignId('opd_penanggung_jawab_id')->nullable()->after('sumber_data')
                ->constrained('opd')->nullOnDelete();
            $table->date('tanggal_data')->nullable()->after('opd_penanggung_jawab_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('map_types', function (Blueprint $table) {
            $table->dropForeign(['opd_penanggung_jawab_id']);
            $table->dropColumn(['sumber_data', 'opd_penanggung_jawab_id', 'tanggal_data']);
        });
    }
};
