<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Simplifikasi Layer (2026-10-06, atas permintaan user): `layers.map_type_id`
 * dan `layers.visibility` dihapus.
 *
 * `visibility` TIDAK PERNAH benar-benar dibaca/ditegakkan untuk kontrol akses
 * di mana pun (dicek lewat grep sebelum migration ini ditulis) — murni field
 * yang diisi lewat form tapi tidak punya konsumen, aman dihapus tanpa
 * pengganti.
 *
 * `map_type_id` jauh lebih dalam: dia kunci yang menggerakkan sistem
 * "Metadata Dinamis per Jenis Peta" (MapTypeDynamicAttribute, validasi field
 * dinamis di SpatialLayerFeatureController/SpatialMapController) dan fitur
 * "Ubah Jenis Peta" massal di halaman Daftar Layer. Konsumen kode ini SUDAH
 * menjaga diri lewat `if (! $layer->map_type_id)` (lihat
 * SpatialLayerFeatureController::activeDynamicAttributesFor(),
 * SpatialMapController), jadi begitu kolomnya hilang (Eloquent membaca atribut
 * tak dikenal sebagai null, bukan error), fitur dinamis itu otomatis selalu
 * kosong — TIDAK perlu migration data tambahan. Tabel `map_types`/
 * `map_type_dynamic_attributes`/`metadata_definitions` dan halaman admin
 * "Jenis Peta" (MapTypeController) TIDAK disentuh — permintaan user cuma
 * mencabut kaitan `layers` ke sana, bukan menghapus master data Jenis Peta
 * itu sendiri. Fitur "Ubah Jenis Peta" massal (bulkUpdateMapType()) dihapus
 * terpisah di kode (SpatialLayerController + routes), karena menulis
 * langsung ke kolom yang sekarang tidak ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS ix_layers_v3_status_vis');
        DB::statement('ALTER TABLE layers DROP CONSTRAINT IF EXISTS ck_layers_v3_visibility');
        DB::statement('ALTER TABLE layers DROP CONSTRAINT IF EXISTS layers_v3_map_type_id_foreign');

        Schema::table('layers', function (Blueprint $table) {
            $table->dropColumn(['map_type_id', 'visibility']);
        });

        DB::statement('CREATE INDEX ix_layers_v3_status ON layers (status) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS ix_layers_v3_status');

        Schema::table('layers', function (Blueprint $table) {
            $table->foreignId('map_type_id')->nullable()->after('category_node_id')->constrained('map_types')->nullOnDelete();
            $table->string('visibility', 20)->default('public')->after('default_style_id');
        });

        DB::statement("ALTER TABLE layers ADD CONSTRAINT ck_layers_v3_visibility CHECK (visibility IN ('public','internal','private'))");
        DB::statement('CREATE INDEX ix_layers_v3_status_vis ON layers (status, visibility) WHERE deleted_at IS NULL');
    }
};
