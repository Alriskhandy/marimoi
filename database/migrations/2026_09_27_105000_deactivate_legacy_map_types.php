<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Perbaikan atas migration create_map_types_table: seed awal men-set is_active=true
     * untuk seluruh 5 map_types, padahal migration 2026_09_19_075917_merge_legacy_map_types_into_tematik
     * sudah menggabungkan psd/psn/pokir_dprd/usulan_musrenbang ke tematik — keempatnya tidak
     * boleh lagi jadi opsi valid di form kategori admin (lihat BackendTematikOnlyTest
     * ::test_category_index_rejects_legacy_types). Baris tetap ada (data lama yang masih
     * berjenis legacy tetap bisa dibaca via legacy_category_id/relasi), hanya dinonaktifkan
     * agar tidak muncul di MapType::active() yang dipakai validator/dropdown baru.
     */
    public function up(): void
    {
        DB::table('map_types')
            ->whereIn('slug', ['psd', 'psn', 'pokir_dprd', 'usulan_musrenbang'])
            ->update(['is_active' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('map_types')
            ->whereIn('slug', ['psd', 'psn', 'pokir_dprd', 'usulan_musrenbang'])
            ->update(['is_active' => true]);
    }
};
