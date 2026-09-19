<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Gabungkan PSD, PSN, Pokir DPRD, dan Usulan Musrenbang ke Peta Tematik.
     */
    public function up(): void
    {
        DB::table('data_spatial')
            ->where('data_type', '!=', 'tematik')
            ->update(['data_type' => 'tematik', 'sub_type' => null]);

        DB::table('categories')
            ->whereIn('type', ['psd', 'psn', 'pokir_dprd', 'usulan_musrenbang'])
            ->update(['type' => 'tematik']);
    }

    /**
     * Penggabungan tidak dapat dibalik karena tipe asal tidak disimpan.
     */
    public function down(): void
    {
        //
    }
};
