<?php

namespace Tests\Feature;

use App\Models\MapType;
use App\Models\MetadataDefinition;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SpatialLayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class MapTypeManagementTest extends TestCase
{
    use RefreshDatabase;

    private function roleWith(string $slug, array $permissions = []): Role
    {
        $role = Role::create(['name' => ucfirst($slug), 'slug' => $slug, 'description' => null]);

        foreach ($permissions as $name) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }

        return $role;
    }

    private function userFor(Role $role): User
    {
        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_user_without_permission_cannot_access_map_types(): void
    {
        $user = $this->userFor($this->roleWith('admin-bappeda'));

        $this->actingAs($user)->get(route('map-types.index'))->assertForbidden();
    }

    /**
     * Regresi: _form.blade.php sempat gagal kompilasi (direktif @json() Blade tidak
     * menangani argumen array literal multi-baris dengan benar, hasil kompilasi
     * terpotong jadi PHP tidak valid) — TIDAK pernah ketahuan dari test store()/
     * update() manapun karena tidak ada yang benar-benar GET & render halaman ini.
     * Wajib ada test yang benar-benar merender index/create/edit, bukan cuma POST/PUT.
     */
    public function test_index_page_renders_without_compile_error(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));

        $this->actingAs($admin)->get(route('map-types.index'))->assertOk();
    }

    /**
     * Regresi: "Tambah Jenis Peta" adalah halaman penuh tersendiri (bukan modal di
     * index) — index cuma link biasa ke `map-types.create`, form action ke
     * `map-types.store`, dan field diperbaiki (Nama didahulukan, Slug auto-terisi
     * dari Nama lewat JS, label wajib diberi tanda *).
     */
    public function test_create_page_is_a_full_page_not_a_modal(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));

        $this->actingAs($admin)->get(route('map-types.index'))
            ->assertOk()
            ->assertSee(route('map-types.create'), false)
            ->assertDontSee('id="addMapTypeModal"', false);

        $response = $this->actingAs($admin)->get(route('map-types.create'));

        $response->assertOk()
            ->assertSee(route('map-types.store'), false)
            ->assertDontSee('data-bs-toggle="modal"', false)
            ->assertSee('id="map_type_nama"', false)
            ->assertSee('id="map_type_slug"', false)
            ->assertSee('readonly', false);
    }

    /**
     * Regresi: field Icon dihapus total dari MapType (model, kolom DB, form, index) —
     * sengaja tidak pernah dipakai di UI manapun.
     */
    public function test_icon_field_is_removed_from_map_type(): void
    {
        $this->assertFalse(Schema::hasColumn('map_types', 'icon'));
        $this->assertNotContains('icon', (new MapType)->getFillable());

        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));
        $response = $this->actingAs($admin)->get(route('map-types.create'));

        $response->assertOk()
            ->assertDontSee('name="icon"', false)
            ->assertDontSee('>Icon<', false);
    }

    /**
     * Regresi Opsi B (docs/marimoi v2/03_plan/14-penyesuaian-database-jenis-peta.md
     * Bagian 6): section "Metadata" satu tabel (`#dynamic-attributes-table`) berisi
     * atribut dinamis (existing/custom), diisi lewat katalog/"Buat Atribut Baru".
     * sumber_data/opd_penanggung_jawab/tanggal_data TIDAK LAGI jadi kolom input di
     * form ini (dulu "Atribut Utama" wajib diketik manual) — ketiganya sekarang
     * dipasang otomatis sebagai definisi `is_system=true` setelah Jenis disimpan
     * (lihat test_store_automatically_attaches_core_attributes_as_wajib()), jadi
     * saat membuat Jenis baru (belum ada id) tidak ada apa pun untuk ditampilkan.
     */
    public function test_create_form_no_longer_has_manual_core_attribute_inputs(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));

        $response = $this->actingAs($admin)->get(route('map-types.create'));

        $response->assertOk()
            ->assertSee('Metadata')
            ->assertDontSee('Metadata Utama')
            ->assertDontSee('Metadata Dinamis')
            ->assertDontSee('name="sumber_data"', false)
            ->assertDontSee('name="opd_penanggung_jawab_id"', false)
            ->assertDontSee('name="tanggal_data"', false)
            ->assertDontSee('<code>sumber_data</code>', false)
            ->assertDontSee('<code>opd_penanggung_jawab_id</code>', false)
            ->assertDontSee('<code>tanggal_data</code>', false)
            ->assertSee('otomatis terpasang')
            ->assertSee('id="dynamic-attributes-table"', false)
            ->assertSee('Nama/Key')
            ->assertSee('Nilai (Label)')
            ->assertSee('Satuan')
            // 4 definisi is_system lama tidak di-render sebagai daftar tetap — cuma
            // bisa ditemukan lewat pencarian katalog (AJAX).
            ->assertDontSee('<code>pagu</code>', false)
            ->assertSee('id="catalog-search-input"', false)
            ->assertSee('id="btn-add-custom-attribute"', false);
    }

    /**
     * Regresi: dulu "Buat Atribut Baru" minta user mengetik Nama/Key DAN Label
     * secara terpisah (dua input untuk 1 konsep yang sama) — sekarang Nama/Key
     * (`attr-kode`) disabled (beda warna, jelas non-interaktif) & otomatis
     * di-generate dari Label lewat JS (pola sama dengan Slug<-Nama di
     * "Informasi Dasar"), user cuma mengetik Label.
     */
    public function test_new_attribute_kode_input_is_disabled_and_derived_from_label(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));

        $response = $this->actingAs($admin)->get(route('map-types.create'));

        $response->assertOk()
            ->assertSee('class="form-control form-control-sm attr-kode" disabled', false)
            ->assertSee('placeholder="otomatis dari Label"', false)
            ->assertSee("querySelector('.attr-kode').value = e.target.value", false);
    }

    /**
     * Regresi: setelah Jenis disimpan, 3 atribut inti tampil di halaman edit
     * sebagai data JSON yang dipakai JS untuk render baris "picked" non-interaktif
     * (badge "Wajib (bawaan)", tanpa tombol hapus) — lihat is_core di
     * $existingAttributesForJs pada _form.blade.php.
     */
    public function test_edit_form_lists_core_attributes_as_non_removable(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));
        $mapType = MapType::where('slug', 'tematik')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('map-types.edit', $mapType));

        $response->assertOk()
            ->assertSee('"kode":"sumber_data"', false)
            ->assertSee('"kode":"opd_penanggung_jawab"', false)
            ->assertSee('"kode":"tanggal_data"', false)
            ->assertSee('"is_core":true', false);
    }

    public function test_edit_page_renders_without_compile_error_and_includes_existing_dynamic_attribute(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));
        $mapType = MapType::where('slug', 'tematik')->firstOrFail();
        $pagu = MetadataDefinition::where('kode', 'pagu')->firstOrFail();
        $mapType->dynamicAttributes()->create(['metadata_definition_id' => $pagu->id]);

        $this->actingAs($admin)->get(route('map-types.edit', $mapType))->assertOk();
    }

    /**
     * Regresi: edit & detail jadi SATU halaman (bukan dua halaman/modal terpisah)
     * — halaman edit menampilkan form ubah sekaligus ringkasan Layer yang memakai
     * Jenis ini.
     */
    public function test_super_admin_can_manage_map_types(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));

        $this->actingAs($admin)->get(route('map-types.index'))->assertOk();

        $response = $this->actingAs($admin)->post(route('map-types.store'), [
            'slug' => 'rawan_bencana',
            'nama' => 'Kawasan Rawan Bencana',
            'urutan' => 6,
        ]);
        $response->assertRedirect();

        $this->assertDatabaseHas('map_types', ['slug' => 'rawan_bencana', 'nama' => 'Kawasan Rawan Bencana']);
    }

    public function test_store_rejects_duplicate_slug(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));

        // 'tematik' sudah ada dari seed migration create_map_types_table.
        $this->actingAs($admin)
            ->post(route('map-types.store'), ['slug' => 'tematik', 'nama' => 'Duplikat'])
            ->assertSessionHasErrors('slug');
    }

    /**
     * Regresi: sumber_data/opd_penanggung_jawab_id/tanggal_data DIHAPUS dari
     * map_types & form Jenis Peta (dulu "Metadata Utama" wajib diketik manual) —
     * sekarang store sukses tanpa field-field itu sama sekali, lihat
     * test_store_automatically_attaches_core_attributes_as_wajib() untuk
     * penggantinya (dipasang otomatis lewat metadata_definitions).
     */
    public function test_store_succeeds_without_the_removed_core_attribute_fields(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));

        $this->actingAs($admin)
            ->post(route('map-types.store'), ['slug' => 'tanpa_metadata', 'nama' => 'Tanpa Metadata'])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('map_types', ['slug' => 'tanpa_metadata']);
    }

    /**
     * Regresi: sumber_data/opd_penanggung_jawab/tanggal_data dipasang OTOMATIS &
     * wajib ke Jenis Peta baru lewat MapTypeController::syncCoreAttributes() —
     * bukan kolom yang diketik user di form (lihat migrasi
     * move_map_type_core_attributes_to_metadata_definitions).
     */
    public function test_store_automatically_attaches_core_attributes_as_wajib(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));

        $this->actingAs($admin)->post(route('map-types.store'), ['slug' => 'otomatis', 'nama' => 'Otomatis']);

        $mapType = MapType::where('slug', 'otomatis')->firstOrFail();

        foreach (['sumber_data', 'opd_penanggung_jawab', 'tanggal_data'] as $kode) {
            $definition = MetadataDefinition::where('kode', $kode)->where('is_system', true)->firstOrFail();
            $this->assertDatabaseHas('map_type_dynamic_attributes', [
                'map_type_id' => $mapType->id,
                'metadata_definition_id' => $definition->id,
                'is_wajib' => true,
            ]);
        }
    }

    /**
     * Regresi: core attributes "tidak bisa diubah" lewat form — mengirim
     * dynamic_attributes kosong (seolah user menghapus semua baris) TIDAK boleh
     * melepas 3 atribut inti, karena server selalu memaksa sinkronnya sendiri.
     */
    public function test_core_attributes_cannot_be_removed_via_update(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));
        $mapType = MapType::where('slug', 'tematik')->firstOrFail();
        $sumberData = MetadataDefinition::where('kode', 'sumber_data')->firstOrFail();

        $this->actingAs($admin)->put(route('map-types.update', $mapType), [
            'slug' => 'tematik',
            'nama' => $mapType->nama,
            'dynamic_attributes' => [],
        ]);

        $this->assertDatabaseHas('map_type_dynamic_attributes', [
            'map_type_id' => $mapType->id,
            'metadata_definition_id' => $sumberData->id,
            'is_wajib' => true,
        ]);
    }

    public function test_update_changes_map_type_fields(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));
        $mapType = MapType::where('slug', 'tematik')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('map-types.update', $mapType), [
                'slug' => 'tematik', 'nama' => 'Peta Tematik Baru',
            ])
            ->assertRedirect(route('map-types.edit', $mapType));

        $this->assertDatabaseHas('map_types', ['id' => $mapType->id, 'nama' => 'Peta Tematik Baru']);
    }

    public function test_update_persists_dynamic_attributes(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));
        $mapType = MapType::where('slug', 'tematik')->firstOrFail();
        $pagu = MetadataDefinition::where('kode', 'pagu')->firstOrFail();

        $this->actingAs($admin)->put(route('map-types.update', $mapType), [
            'slug' => 'tematik',
            'nama' => $mapType->nama,
            'dynamic_attributes' => [
                ['metadata_definition_id' => $pagu->id, 'is_wajib' => '1'],
            ],
        ]);

        $this->assertDatabaseHas('map_type_dynamic_attributes', [
            'map_type_id' => $mapType->id,
            'metadata_definition_id' => $pagu->id,
            'is_wajib' => true,
        ]);
    }

    /**
     * Regresi Opsi B: mengirim `kode/label/satuan/data_type` (tanpa
     * `metadata_definition_id`) berarti "buat definisi baru" — `firstOrCreate` ke
     * katalog `metadata_definitions` dulu, baru pivot dibuat.
     */
    public function test_update_creates_new_metadata_definition_for_custom_attribute(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));
        $mapType = MapType::where('slug', 'tematik')->firstOrFail();

        $this->actingAs($admin)->put(route('map-types.update', $mapType), [
            'slug' => 'tematik',
            'nama' => $mapType->nama,
            'dynamic_attributes' => [
                ['kode' => 'lebar_jalan', 'label' => 'Lebar Jalan', 'satuan' => 'meter', 'data_type' => 'text', 'is_wajib' => '0'],
            ],
        ]);

        $this->assertDatabaseHas('metadata_definitions', ['kode' => 'lebar_jalan', 'label' => 'Lebar Jalan', 'is_system' => false]);
        $definition = MetadataDefinition::where('kode', 'lebar_jalan')->firstOrFail();
        $this->assertDatabaseHas('map_type_dynamic_attributes', [
            'map_type_id' => $mapType->id,
            'metadata_definition_id' => $definition->id,
        ]);
    }

    public function test_removing_a_dynamic_attribute_row_deletes_it(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));
        $mapType = MapType::where('slug', 'tematik')->firstOrFail();
        $pagu = MetadataDefinition::where('kode', 'pagu')->firstOrFail();
        $mapType->dynamicAttributes()->create(['metadata_definition_id' => $pagu->id]);

        $this->actingAs($admin)->put(route('map-types.update', $mapType), [
            'slug' => 'tematik',
            'nama' => $mapType->nama,
            'dynamic_attributes' => [],
        ]);

        $this->assertDatabaseMissing('map_type_dynamic_attributes', ['map_type_id' => $mapType->id, 'metadata_definition_id' => $pagu->id]);
    }

    public function test_destroy_blocks_deletion_when_still_used_by_a_layer(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));
        $mapType = MapType::where('slug', 'tematik')->firstOrFail();
        $categoryId = DB::table('categories_v3')->insertGetId([
            'id' => (string) Str::uuid(),
            'name' => 'Kategori Uji',
            'slug' => 'kategori-uji-'.Str::random(6),
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id');
        SpatialLayer::create(['category_id' => $categoryId, 'layer_type_id' => 4, 'code' => 'layer-jalan', 'slug' => 'jalan', 'name' => 'Jalan', 'map_type_id' => $mapType->id]);

        $this->actingAs($admin)
            ->delete(route('map-types.destroy', $mapType))
            ->assertRedirect();

        $this->assertDatabaseHas('map_types', ['id' => $mapType->id]);
    }

    public function test_destroy_succeeds_when_not_used(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));
        $mapType = MapType::create(['slug' => 'kosong', 'nama' => 'Kosong']);

        $this->actingAs($admin)
            ->delete(route('map-types.destroy', $mapType))
            ->assertRedirect(route('map-types.index'));

        $this->assertDatabaseMissing('map_types', ['id' => $mapType->id]);
    }

    /**
     * Regresi: index dulu memakai edit modal yang form action-nya sempat salah
     * (url() bukan route(), mengabaikan prefix 'dashboard'). Sekarang edit pindah
     * ke halaman penuh (bukan modal) — pastikan link edit di index mengarah ke
     * route yang benar (dengan prefix dashboard), bukan URL yang ditulis manual.
     */
    public function test_index_page_edit_link_uses_correct_dashboard_prefixed_route(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));
        $mapType = MapType::where('slug', 'tematik')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('map-types.index'));

        $response->assertOk();
        $response->assertSee(route('map-types.edit', $mapType), false);
    }

    /**
     * Regresi: tampilan index diperbaiki — stats card (Total Jenis/Aktif/Nonaktif/
     * Total Layer), toolbar cari + per-halaman via DataTables, kolom OPD Penanggung
     * Jawab, dan tombol hapus pakai pola data-confirm (bukan onsubmit=confirm() polos).
     */
    public function test_index_page_shows_stats_cards_and_delete_button(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));
        $mapType = MapType::where('slug', 'tematik')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('map-types.index'));

        $response->assertOk()
            ->assertSee('Total Jenis')
            ->assertSee('Aktif')
            ->assertSee('Nonaktif')
            ->assertSee('Total Layer')
            ->assertSee('id="mapTypesTable"', false)
            ->assertSee('id="tableSearch"', false)
            ->assertSee('data-confirm="delete"', false)
            ->assertSee(route('map-types.destroy', $mapType), false);
    }

    /**
     * Regresi: kolom Sumber Data & OPD Penanggung Jawab dihapus dari index, diganti
     * "Atribut Utama" (hitung MapTypeDynamicAttribute tipe placeholder aktif) dan
     * "Atribut Tambahan" (tipe custom aktif) — atribut nonaktif tidak ikut terhitung.
     */
    public function test_index_page_shows_atribut_utama_and_tambahan_columns_instead_of_sumber_data_and_opd(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));
        $mapType = MapType::where('slug', 'tematik')->firstOrFail();
        $pagu = MetadataDefinition::where('kode', 'pagu')->firstOrFail();
        $realisasiFisik = MetadataDefinition::where('kode', 'realisasi_fisik')->firstOrFail();
        $catatan = MetadataDefinition::create(['kode' => 'catatan-'.uniqid(), 'label' => 'Catatan', 'is_system' => false]);
        $mapType->dynamicAttributes()->create(['metadata_definition_id' => $pagu->id, 'is_active' => true]);
        $mapType->dynamicAttributes()->create(['metadata_definition_id' => $realisasiFisik->id, 'is_active' => false]);
        $mapType->dynamicAttributes()->create(['metadata_definition_id' => $catatan->id, 'is_active' => true]);

        $response = $this->actingAs($admin)->get(route('map-types.index'));

        $response->assertOk()
            ->assertSee('Atribut Utama')
            ->assertSee('Atribut Tambahan')
            ->assertDontSee('<th>Sumber Data</th>', false)
            ->assertDontSee('<th>OPD Penanggung Jawab</th>', false);
    }

    /**
     * Regresi: index menegaskan Jenis Peta BUKAN pengelompokan Layer, melainkan
     * definisi atribut/metadata acuan — user wajib pilih Jenis lalu isi Atribut
     * Utama/Tambahan yang sudah ditentukan saat menambah Layer baru.
     */
    /**
     * Regresi: kolom "Jumlah Layer" diganti nama jadi "Dipakai di Layer" (+ tooltip)
     * supaya tidak terkesan kolom pengelompokan/kategori — Jenis Peta cuma acuan
     * atribut, bukan pengelompokan Layer.
     *
     * Catatan: banner info penjelas yang sebelumnya ada di atas tabel sempat
     * dihapus dari file ini di luar sesi kerja ini (bukan oleh perubahan yang
     * sedang dikerjakan) — assertion untuk banner tersebut sengaja tidak
     * dipertahankan di sini supaya test tetap merefleksikan isi file yang
     * sebenarnya, bukan versi yang sudah tidak ada.
     */
    public function test_index_page_renames_jumlah_layer_column_to_avoid_grouping_implication(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));

        $response = $this->actingAs($admin)->get(route('map-types.index'));

        $response->assertOk()->assertSee('Dipakai di Layer');
    }

    public function test_activating_a_previously_inactive_map_type_via_update_route_succeeds(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));
        $mapType = MapType::where('slug', 'psd')->firstOrFail();
        $this->assertFalse($mapType->is_active);

        $this->actingAs($admin)
            ->put(route('map-types.update', $mapType), [
                'slug' => 'psd',
                'nama' => $mapType->nama,
                'is_active' => '1',
            ])
            ->assertRedirect(route('map-types.edit', $mapType));

        $this->assertDatabaseHas('map_types', ['id' => $mapType->id, 'is_active' => true]);
    }
}
