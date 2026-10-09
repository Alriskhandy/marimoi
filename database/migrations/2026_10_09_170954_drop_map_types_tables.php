<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Retire modul "Jenis Peta" seutuhnya: tabel `map_types` +
     * `map_type_dynamic_attributes`, plus permission `map-types.manage`.
     *
     * Modul ini sudah tidak punya konsumen sejak kolom `layers.map_type_id`
     * dilepas (migration drop_map_type_id_and_visibility_from_layers_table):
     * tanpa kolom itu tidak ada Layer yang bisa dikaitkan ke sebuah Jenis,
     * jadi seluruh pembaca atribut dinamis sudah mengembalikan koleksi kosong
     * dan form "Metadata Dinamis" tidak pernah merender field apa pun.
     *
     * Katalog `metadata_definitions` SENGAJA TIDAK disentuh — isinya masih
     * jadi target `layer_attribute_mappings.attribute_definition_id` (pemetaan
     * kolom hasil impor) dan di-seed command marimoi:migrate-metadata-definitions.
     */
    public function up(): void
    {
        // `spatial_layers_legacy_v2` adalah arsip beku pra-v3 yang masih dibaca
        // SpatialFeedback, jadi kolom map_type_id-nya DIBIARKAN sebagai integer
        // inert (nilai historisnya tidak dibuang); cuma FK ke map_types yang
        // harus lepas supaya tabel induknya bisa di-drop. Tidak ada kode yang
        // membaca kolom itu lagi sejak command marimoi:granularize-map-types
        // dihapus bersama migration ini.
        if (Schema::hasColumn('spatial_layers_legacy_v2', 'map_type_id')) {
            DB::statement('ALTER TABLE spatial_layers_legacy_v2 DROP CONSTRAINT IF EXISTS spatial_layers_map_type_id_foreign');
        }

        Schema::dropIfExists('map_type_dynamic_attributes');
        Schema::dropIfExists('map_types');

        $this->forgetPermission('map-types.manage');
    }

    public function down(): void
    {
        Schema::create('map_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->jsonb('konfigurasi')->nullable();
            $table->timestamps();
        });

        // Lima baris bawaan dari create_map_types_table, dengan `is_active`
        // sesuai deactivate_legacy_map_types. Baris Jenis granular hasil
        // marimoi:granularize-map-types dan seluruh isi pivot TIDAK bisa
        // dipulihkan di sini — rollback memulihkan struktur, bukan data.
        DB::table('map_types')->insert([
            ['slug' => 'tematik', 'nama' => 'Peta Tematik', 'urutan' => 1, 'is_active' => false, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'usulan_musrenbang', 'nama' => 'Usulan Musrenbang', 'urutan' => 2, 'is_active' => false, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'pokir_dprd', 'nama' => 'Pokok Pikiran DPRD', 'urutan' => 3, 'is_active' => false, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'psd', 'nama' => 'Proyek Strategis Daerah', 'urutan' => 4, 'is_active' => false, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'psn', 'nama' => 'Proyek Strategis Nasional', 'urutan' => 5, 'is_active' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::create('map_type_dynamic_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('map_type_id')->constrained('map_types')->cascadeOnDelete();
            $table->foreignId('metadata_definition_id')->constrained('metadata_definitions')->cascadeOnDelete();
            $table->boolean('is_wajib')->default(false);
            $table->boolean('is_enabled')->default(true);
            $table->integer('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['map_type_id', 'metadata_definition_id']);
        });

        if (Schema::hasColumn('spatial_layers_legacy_v2', 'map_type_id')) {
            // Nilai yatim (Jenis granular yang tidak ikut dipulihkan di atas)
            // dinolkan dulu, kalau tidak FK-nya menolak terpasang.
            DB::statement('UPDATE spatial_layers_legacy_v2 SET map_type_id = NULL WHERE map_type_id IS NOT NULL AND map_type_id NOT IN (SELECT id FROM map_types)');
            DB::statement('ALTER TABLE spatial_layers_legacy_v2 ADD CONSTRAINT spatial_layers_map_type_id_foreign FOREIGN KEY (map_type_id) REFERENCES map_types (id) ON DELETE SET NULL');
        }

        $permissionId = DB::table('permissions')->insertGetId([
            'name' => 'map-types.manage',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Super admin saja — pemberian ke role lain adalah keputusan operator,
        // bukan sesuatu yang boleh ditebak migration.
        $superAdminRoleIds = DB::table('roles')->where('slug', 'super-admin')->pluck('id');

        foreach ($superAdminRoleIds as $roleId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }

        app()['cache']->forget('spatie.permission.cache');
    }

    private function forgetPermission(string $name): void
    {
        $ids = DB::table('permissions')->where('name', $name)->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        app()['cache']->forget('spatie.permission.cache');
    }
};
