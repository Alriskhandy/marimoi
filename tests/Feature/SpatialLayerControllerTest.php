<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\SpatialLayer;
use App\Models\SpatialLayerFeature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regresi untuk SpatialLayerController — skema v3 (docs/marimoi v2/
 * db-schema-v3.md, plan mellow-weaving-eclipse Fase 3). Layer TIDAK LAGI
 * bertingkat antar-sesama (parent_id/cycle-check v2 dihapus) — setiap Layer
 * wajib punya `category_id` (categories_v3), opsional `category_node_id`.
 * Test lama yang menguji tree/cycle antar-layer (test_deeply_nested_tree_is_allowed,
 * test_direct_self_parent_cycle_is_rejected, test_chained_cycle_through_descendant_is_rejected,
 * test_destroy_blocked_when_layer_has_children) DIHAPUS di sini — bukan
 * dihapus sembarangan, melainkan karena fungsionalitas yang diuji sudah
 * tidak ada lagi secara sengaja (lihat plan Keputusan #4 & dokumen §1.2).
 */
class SpatialLayerControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'description' => null]);
        foreach (['spatial-layers.view', 'spatial-layers.create', 'spatial-layers.edit', 'spatial-layers.delete'] as $perm) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']));
        }

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function categoryId(string $name = 'Kategori Uji'): string
    {
        return DB::table('categories_v3')->insertGetId([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'slug' => Str::slug($name.'-'.Str::random(6)),
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id');
    }

    private function createLayer(array $attributes = []): SpatialLayer
    {
        return SpatialLayer::create(array_merge([
            'category_id' => $this->categoryId(),
            'layer_type_id' => 4,
            'code' => 'layer-'.Str::random(8),
            'slug' => 'layer-'.Str::random(8),
            'name' => 'Layer Uji',
        ], $attributes));
    }

    public function test_admin_can_create_a_layer(): void
    {
        $admin = $this->admin();
        $categoryId = $this->categoryId();

        $response = $this->actingAs($admin)->post(route('spatial-layers.store'), [
            'category_id' => $categoryId,
            'layer_type_id' => 4,
            'name' => 'Jalan Provinsi',
        ]);

        $this->assertDatabaseHas('layers', ['name' => 'Jalan Provinsi', 'category_id' => $categoryId, 'wizard_step' => 2]);

        $layer = SpatialLayer::where('name', 'Jalan Provinsi')->first();
        $response->assertRedirect(route('spatial-layers.wizard', $layer));
    }

    public function test_store_requires_category(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('spatial-layers.store'), [
            'name' => 'Tanpa Kategori',
        ])->assertSessionHasErrors('category_id');
    }

    public function test_category_node_must_belong_to_selected_category(): void
    {
        $admin = $this->admin();
        $categoryA = $this->categoryId('Kategori A');
        $categoryB = $this->categoryId('Kategori B');
        $nodeOfB = DB::table('category_nodes')->insertGetId([
            'id' => (string) Str::uuid(),
            'category_id' => $categoryB,
            'name' => 'Node B',
            'slug' => 'node-b-'.Str::random(6),
            'depth' => 2,
            'path' => DB::raw("'c1.c2'::ltree"),
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id');

        $this->actingAs($admin)->post(route('spatial-layers.store'), [
            'category_id' => $categoryA,
            'category_node_id' => $nodeOfB,
            'layer_type_id' => 4,
            'name' => 'Salah Subkategori',
        ])->assertSessionHasErrors('category_node_id');
    }

    public function test_destroy_blocked_when_layer_has_features(): void
    {
        $admin = $this->admin();
        $layer = $this->createLayer(['name' => 'Layer Berdata Uji']);
        SpatialLayerFeature::create(['layer_id' => $layer->id, 'geom' => DB::raw("ST_GeomFromText('POINT(127.8 1.5)', 4326)")]);

        $this->actingAs($admin)->delete(route('spatial-layers.destroy', $layer))->assertRedirect();

        $this->assertDatabaseHas('layers', ['id' => $layer->id]);
    }

    public function test_user_without_permission_cannot_access(): void
    {
        $user = User::factory()->create(['role_id' => Role::create(['name' => 'Admin OPD', 'slug' => 'admin-opd', 'description' => null])->id]);

        $this->actingAs($user)->get(route('spatial-layers.index'))->assertForbidden();
    }

    /**
     * Regresi: halaman ini perlu benar-benar dirender (GET), bukan cuma dites lewat
     * store()/update() — pola bug yang sama (kesalahan kompilasi Blade lolos dari
     * test) sempat terjadi di map-types/_form.blade.php.
     */
    public function test_create_page_renders(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('spatial-layers.create'))
            ->assertOk()
            ->assertSee('step-indicator', false)
            ->assertSee('Informasi Layer', false);
    }

    /**
     * Regresi: halaman edit terpisah sudah dihapus — mengubah Informasi Layer kini
     * lewat modal di halaman detail (pola sama seperti modal edit Kategori di
     * /dashboard/categories), form modal-nya tetap kirim ke route update yang sama.
     */
    public function test_show_page_has_edit_modal_instead_of_separate_edit_page(): void
    {
        $admin = $this->admin();
        $layer = $this->createLayer(['name' => 'Root']);

        $response = $this->actingAs($admin)->get(route('spatial-layers.show', $layer));

        $response->assertOk()
            ->assertSee('id="editLayerModal"', false)
            ->assertSee(route('spatial-layers.update', $layer), false)
            ->assertDontSee('>Kelola</a>', false);
    }

    public function test_spatial_layers_edit_route_no_longer_exists(): void
    {
        $this->assertFalse(Route::has('spatial-layers.edit'));
    }

    /**
     * Regresi: modal edit Layer disamakan (copy-paste + modifikasi field) dengan
     * modal edit Kategori di categories/index.blade.php — widget icon-picker &
     * color-picker (class CSS + struktur DOM yang sama), bukan form polos.
     */
    public function test_edit_modal_matches_categories_edit_modal_widgets(): void
    {
        $admin = $this->admin();
        $layer = $this->createLayer(['name' => 'Layer Modal Uji']);

        $response = $this->actingAs($admin)->get(route('spatial-layers.show', $layer));

        $response->assertOk()
            ->assertSee('class="color-picker-widget"', false)
            ->assertSee('id="layer_edit_colorSwatches"', false)
            ->assertSee('class="icon-picker-grid" id="layer_edit_iconGrid"', false)
            ->assertSee('id="layer_edit_iconSearch"', false)
            ->assertSee('class="settings-switch-group"', false)
            ->assertSee('name="category_id"', false)
            ->assertSee('btn-gradient-warning', false);
    }

    public function test_index_page_renders_tree(): void
    {
        $admin = $this->admin();
        $this->createLayer(['name' => 'Root Tree Uji']);

        $this->actingAs($admin)->get(route('spatial-layers.index'))->assertOk()->assertSee('Root Tree Uji');
    }

    /**
     * Regresi (2026-10-06, plan rippling-frolicking-ladybug): "Tambah Layer" di
     * index TIDAK LAGI modal — sekarang link ke wizard 4 tahap di halaman
     * /spatial-layers/create (lihat LayerWizardController). Test ini
     * membalik makna dari versi sebelumnya (`test_index_page_has_add_layer_modal_
     * instead_of_create_page_link`), yang justru menegaskan modal ADA.
     */
    public function test_index_page_links_to_create_wizard_instead_of_modal(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get(route('spatial-layers.index'));

        $response->assertOk()
            ->assertSee('href="'.route('spatial-layers.create').'"', false)
            ->assertDontSee('id="addLayerModal"', false)
            ->assertDontSee('id="addLayerForm"', false);
    }

    /**
     * Regresi: tampilan index menampilkan stats card ringkas (Total Layer,
     * Punya Data Spasial, Marker Aktif, Status Aktif).
     */
    public function test_index_page_shows_stats_cards(): void
    {
        $admin = $this->admin();
        $this->createLayer(['name' => 'Layer Stats Uji']);

        $response = $this->actingAs($admin)->get(route('spatial-layers.index'));

        $response->assertOk()
            ->assertSee('Total Layer')
            ->assertSee('Punya Data Spasial')
            ->assertSee('Marker Aktif');
    }

    /**
     * Regresi: tabel index digabung (Warna/Tipe/Icon -> 1 kolom Style), dan
     * tombol detail (mata) ditambahkan di kolom Aksi.
     *
     * Catatan: kolom Status sempat dihapus pada iterasi UI sebelumnya untuk
     * mengurangi "clutter" (lihat riwayat git), tapi dikembalikan di Fase B
     * (implementasi spec-admin-manajemen-peta.md §5.2 butir 7) karena filter
     * & tampilan status draft/published/archived sekarang jadi kebutuhan inti
     * alur kurasi OPD→Bappeda (R17), bukan sekadar dekorasi.
     */
    public function test_index_table_merges_style_columns_and_links_to_detail_page(): void
    {
        $admin = $this->admin();
        $layer = $this->createLayer(['name' => 'Layer Kolom Uji']);

        $response = $this->actingAs($admin)->get(route('spatial-layers.index'));

        $response->assertOk()
            ->assertDontSee('<th>Warna</th>', false)
            ->assertDontSee('<th>Icon</th>', false)
            ->assertSee('>Tipe Geometri</th>', false)
            ->assertSee('>Status</th>', false)
            ->assertSee(route('spatial-layers.show', $layer), false);
    }

    public function test_show_page_renders_layer_detail(): void
    {
        $admin = $this->admin();
        $layer = $this->createLayer(['name' => 'Layer Detail Uji']);

        $response = $this->actingAs($admin)->get(route('spatial-layers.show', $layer));

        $response->assertOk()
            ->assertSee('Layer Detail Uji');
    }

    /**
     * Regresi: kartu "Peta Data Spasial" dan kartu tabel "Data Spasial" digabung
     * jadi SATU kartu dengan switcher Tabel/Peta (pola sama dengan tombol
     * Tabel/Peta di data_spatial/index.blade.php), bukan dua kartu terpisah lagi.
     */
    public function test_show_page_renders_single_card_with_table_map_switcher(): void
    {
        $admin = $this->admin();
        $layer = $this->createLayer(['name' => 'Layer Peta Uji']);

        $response = $this->actingAs($admin)->get(route('spatial-layers.show', $layer));

        $response->assertOk()
            ->assertSee('id="viewModeTableBtn"', false)
            ->assertSee('id="viewModeMapBtn"', false)
            ->assertSee('id="dataSpasialTableView"', false)
            ->assertSee('id="dataSpasialMapView"', false)
            ->assertSee('id="layerDetailMap"', false)
            ->assertSee('id="layerMapBasemapSwitcher"', false)
            ->assertSee('data-basemap="satelit"', false)
            ->assertSee('<th>Wilayah</th>', false)
            ->assertDontSee('id="layerMapCollapse"', false);
    }

    /**
     * Regresi: card Informasi Layer collapsible (default collapsed sama seperti
     * card peta), tabel Data Spasial dapat search box dan tombol hapus di kolom
     * Aksi.
     */
    public function test_show_page_has_collapsible_info_card_search_and_delete_button(): void
    {
        $admin = $this->admin();
        $layer = $this->createLayer(['name' => 'Layer Aksi Uji']);
        $feature = SpatialLayerFeature::create([
            'layer_id' => $layer->id,
            'label' => 'FTR-001',
            'geom' => DB::raw("ST_GeomFromText('POINT(127.8 1.5)', 4326)"),
        ]);

        $response = $this->actingAs($admin)->get(route('spatial-layers.show', $layer));

        $response->assertOk()
            ->assertSee('class="collapse" id="layerInfoCollapse"', false)
            ->assertSee('id="dataSpasialSearchInput"', false)
            ->assertSee(route('spatial-layers.features.destroy', [$layer, $feature]), false)
            ->assertSee('mdi-delete', false);
    }

    /**
     * Regresi: tombol "Tambah Data Spasial" menuju halaman create feature, dan
     * pilihan "Tampilkan N data" tersedia untuk membatasi jumlah baris yang tampil.
     */
    public function test_show_page_has_add_feature_button_and_per_page_select(): void
    {
        $admin = $this->admin();
        $layer = $this->createLayer(['name' => 'Layer Tambah Uji']);

        $response = $this->actingAs($admin)->get(route('spatial-layers.show', $layer));

        $response->assertOk()
            ->assertSee('Tambah Data Spasial')
            ->assertSee(route('spatial-layers.features.create', $layer), false)
            ->assertSee('id="dataSpasialPerPage"', false)
            ->assertSee('25 / halaman')
            ->assertSee('50 / halaman');
    }

    /**
     * Regresi: tabel Data Spasial sempat cuma sembunyi-tampilkan baris lewat JS
     * manual tanpa pagination sungguhan — sekarang pakai DataTables (pola sama
     * dengan tabel Daftar Layer di spatial-layers/index.blade.php).
     *
     * Filter "Status Metadata" (Lengkap/Belum Lengkap) yang dulu diuji di sini
     * DIHAPUS 2026-10-06 bersama `layers.map_type_id` — status itu dihitung dari
     * atribut dinamis Jenis Peta Layer, yang sekarang selalu kosong untuk semua
     * Layer (lihat SpatialLayerController::show()), jadi labelnya tidak lagi
     * punya sinyal nyata untuk dibedakan.
     */
    public function test_show_page_data_spasial_table_has_pagination(): void
    {
        $admin = $this->admin();
        $layer = $this->createLayer(['name' => 'Layer DataTable Uji']);
        SpatialLayerFeature::create([
            'layer_id' => $layer->id,
            'geom' => DB::raw("ST_GeomFromText('POINT(127.8 1.5)', 4326)"),
            'properties' => ['sumber_data' => 'Uji'],
        ]);
        SpatialLayerFeature::create([
            'layer_id' => $layer->id,
            'geom' => DB::raw("ST_GeomFromText('POINT(127.9 1.6)', 4326)"),
        ]);

        $response = $this->actingAs($admin)->get(route('spatial-layers.show', $layer));

        $response->assertOk()
            ->assertSee('id="dataSpasialPerPage"', false)
            ->assertSee('id="dataSpasialLayerTable"', false);
    }

    /**
     * Detail satu Data Spasial ditampilkan lewat modal saat baris tabel diklik,
     * tanpa pindah ke halaman terpisah — baris harus membawa payload JSON
     * (kode, wilayah, metadata dinamis, url edit/hapus) lewat atribut
     * data-feature, dan markup modal #featureDetailModal harus ada di halaman.
     */
    public function test_show_page_feature_row_carries_detail_payload_for_modal(): void
    {
        $admin = $this->admin();
        $layer = $this->createLayer(['name' => 'Layer Detail Modal Uji']);
        $feature = SpatialLayerFeature::create([
            'layer_id' => $layer->id,
            'label' => 'KODE-001',
            'geom' => DB::raw("ST_GeomFromText('POINT(127.8 1.5)', 4326)"),
        ]);

        $response = $this->actingAs($admin)->get(route('spatial-layers.show', $layer));

        $response->assertOk()
            ->assertSee('id="featureDetailModal"', false)
            ->assertSee('id="featureDetailDeleteForm"', false)
            ->assertSee('data-feature-row', false)
            ->assertSee(e(route('spatial-layers.features.edit', [$layer, $feature])), false)
            ->assertSee('&quot;kode&quot;:&quot;KODE-001&quot;', false);
    }

    /**
     * Regresi: baris "Belum ada Data Spasial" dulu dirender sebagai <tr> statis
     * di tbody, yang ikut dihitung DataTables sebagai 1 data sungguhan — info
     * paginasi jadi salah menampilkan "Menampilkan 1 sampai 1 dari 1 Data
     * Spasial" padahal sebenarnya nol (dilaporkan user lewat screenshot).
     * Sekarang pesan kosong itu dirender lewat opsi emptyTable DataTables,
     * bukan baris tbody, supaya DataTables benar-benar menghitungnya nol.
     */
    public function test_show_page_empty_data_spasial_uses_datatables_empty_message_not_static_row(): void
    {
        $admin = $this->admin();
        $layer = $this->createLayer(['name' => 'Layer Kosong DataTable Uji']);

        $response = $this->actingAs($admin)->get(route('spatial-layers.show', $layer));

        $response->assertOk()
            ->assertSee('Belum ada Data Spasial')
            ->assertSee('Tambah Data Spasial Pertama')
            ->assertSee('emptyTable', false)
            // Tbody tidak boleh punya baris statis lagi — kalau ada, berarti bug
            // "Menampilkan 1 dari 1" lama kembali muncul.
            ->assertDontSee('<td colspan="7" class="text-center py-4 text-muted">', false);
    }

    /**
     * Regresi: halaman detail "diperbagus" dengan breadcrumb & kartu statistik
     * (Jenis Layer, Kategori, Data Spasial, Status) di atas kartu Informasi Layer.
     */
    public function test_show_page_displays_breadcrumb_and_stats_cards(): void
    {
        $admin = $this->admin();
        $layer = $this->createLayer(['name' => 'Root Stats Uji']);
        SpatialLayerFeature::create(['layer_id' => $layer->id, 'geom' => DB::raw("ST_GeomFromText('POINT(127.8 1.5)', 4326)")]);

        $response = $this->actingAs($admin)->get(route('spatial-layers.show', $layer));

        $response->assertOk()
            ->assertSee('breadcrumb', false)
            ->assertSee('Daftar Layer & Data', false)
            ->assertSee('Kategori')
            ->assertSee('Data Spasial')
            ->assertSee('class="stat-value">1</h3>', false);
    }

    /**
     * Regresi: tombol Hapus Layer di halaman detail cuma muncul kalau Layer tidak
     * punya Data Spasial (konsisten dengan validasi di
     * SpatialLayerController::destroy() yang menolak hapus kalau masih dipakai).
     */
    public function test_show_page_hides_delete_button_when_layer_has_features(): void
    {
        $admin = $this->admin();
        $layer = $this->createLayer(['name' => 'Layer Hapus Uji']);
        SpatialLayerFeature::create(['layer_id' => $layer->id, 'geom' => DB::raw("ST_GeomFromText('POINT(127.8 1.5)', 4326)")]);

        $response = $this->actingAs($admin)->get(route('spatial-layers.show', $layer));

        $response->assertOk()->assertDontSee('title="Hapus Layer"', false);
    }

    public function test_show_page_shows_delete_button_when_layer_is_empty(): void
    {
        $admin = $this->admin();
        $layer = $this->createLayer(['name' => 'Layer Kosong Uji']);

        $response = $this->actingAs($admin)->get(route('spatial-layers.show', $layer));

        $response->assertOk()->assertSee('title="Hapus Layer"', false);
    }

    public function test_user_without_permission_cannot_view_detail_page(): void
    {
        $layer = $this->createLayer(['name' => 'Layer Uji']);
        $user = User::factory()->create(['role_id' => Role::create(['name' => 'Admin OPD', 'slug' => 'admin-opd', 'description' => null])->id]);

        $this->actingAs($user)->get(route('spatial-layers.show', $layer))->assertForbidden();
    }

    public function test_index_table_has_no_gambar_column(): void
    {
        $admin = $this->admin();
        $this->createLayer(['name' => 'Layer Kolom Gambar Uji']);

        $this->actingAs($admin)->get(route('spatial-layers.index'))
            ->assertOk()
            ->assertDontSee('<th>Gambar</th>', false);
    }

    /**
     * Fase B (spec-admin-manajemen-peta.md §5.2): layer_type_id dan sort_order
     * sudah ada di skema & model sejak Fase A tapi belum pernah tersimpan
     * lewat form — ini mengunci bahwa form sekarang benar-benar menulisnya.
     *
     * `is_default_on`/`is_queryable`/`is_downloadable`/`min_zoom`/`max_zoom`
     * dihapus 2026-10-06 (lihat migration
     * drop_default_on_download_query_zoom_columns_from_layers_table) — tidak
     * pernah dibaca oleh renderer peta publik mana pun, hanya dicatat lewat
     * form admin yang kini juga sudah tidak menampilkannya lagi.
     */
    public function test_store_saves_layer_type_and_display_properties(): void
    {
        $admin = $this->admin();
        $categoryId = $this->categoryId();
        $layerType = DB::table('layer_types')->where('code', 'vector_point')->first();

        $this->actingAs($admin)->post(route('spatial-layers.store'), [
            'category_id' => $categoryId,
            'layer_type_id' => $layerType->id,
            'name' => 'Layer Properti Uji',
            'sort_order' => 3,
        ]);

        $this->assertDatabaseHas('layers', [
            'name' => 'Layer Properti Uji',
            'layer_type_id' => $layerType->id,
            'sort_order' => 3,
        ]);
    }

    public function test_update_persists_display_properties_without_touching_status(): void
    {
        $role = Role::create(['name' => 'Editor Layer', 'slug' => 'editor-layer', 'description' => null]);
        foreach (['spatial-layers.view', 'spatial-layers.create', 'spatial-layers.edit'] as $perm) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']));
        }
        $editor = User::factory()->create(['role_id' => $role->id]);
        $layer = $this->createLayer(['name' => 'Layer Update Properti', 'status' => 'published', 'published_at' => now()]);

        $this->actingAs($editor)->put(route('spatial-layers.update', $layer), [
            'category_id' => $layer->category_id,
            'name' => 'Layer Update Properti',
            'sort_order' => 9,
        ])->assertRedirect(route('spatial-layers.show', $layer));

        $layer->refresh();
        $this->assertSame(9, $layer->sort_order);
        // Role ini tidak punya permission spatial-layers.publish — status
        // layer published yang sudah ada harus tetap dipertahankan (R17),
        // bukan ikut jatuh ke draft hanya karena form ini tidak mengirim
        // is_active=1 (lihat SpatialLayerController::validated()).
        $this->assertSame('published', $layer->status);
    }

    public function test_index_page_shows_status_and_opd_filter_dropdowns(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get(route('spatial-layers.index'));

        $response->assertOk()
            ->assertSee('id="statusFilter"', false)
            ->assertSee('id="opdFilter"', false);
    }
}
