<?php

namespace Tests\Feature;

use App\Models\MapType;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SpatialLayer;
use App\Models\SpatialLayerFeature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Regresi untuk SpatialLayerController (docs/marimoi v2/04_implementation/
 * 12-implementasi-perbaikan-pemetaan.md Bagian 3.2) — CRUD Layer, tree tanpa batas
 * kedalaman (Keputusan #3), validasi cycle.
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

    private function jenis(): MapType
    {
        return MapType::where('slug', 'tematik')->firstOrFail();
    }

    public function test_admin_can_create_a_layer(): void
    {
        $admin = $this->admin();
        $jenis = $this->jenis();

        $this->actingAs($admin)->post(route('spatial-layers.store'), [
            'map_type_id' => $jenis->id,
            'name' => 'Jalan Provinsi',
        ])->assertRedirect(route('spatial-layers.index'));

        $this->assertDatabaseHas('spatial_layers', ['name' => 'Jalan Provinsi', 'map_type_id' => $jenis->id]);
    }

    public function test_deeply_nested_tree_is_allowed(): void
    {
        $admin = $this->admin();
        $jenis = $this->jenis();
        $level1 = SpatialLayer::create(['slug' => 'l1', 'name' => 'Level 1', 'title' => 'Level 1', 'map_type_id' => $jenis->id]);
        $level2 = SpatialLayer::create(['slug' => 'l2', 'name' => 'Level 2', 'title' => 'Level 2', 'map_type_id' => $jenis->id, 'parent_id' => $level1->id]);
        $level3 = SpatialLayer::create(['slug' => 'l3', 'name' => 'Level 3', 'title' => 'Level 3', 'map_type_id' => $jenis->id, 'parent_id' => $level2->id]);

        $this->actingAs($admin)->post(route('spatial-layers.store'), [
            'map_type_id' => $jenis->id,
            'parent_id' => $level3->id,
            'name' => 'Level 4',
        ])->assertRedirect(route('spatial-layers.index'));

        $level4 = SpatialLayer::where('name', 'Level 4')->firstOrFail();
        $this->assertSame($level3->id, $level4->parent_id);
    }

    public function test_direct_self_parent_cycle_is_rejected(): void
    {
        $admin = $this->admin();
        $jenis = $this->jenis();
        $layer = SpatialLayer::create(['slug' => 'l1', 'name' => 'Layer', 'title' => 'Layer', 'map_type_id' => $jenis->id]);

        $this->actingAs($admin)->put(route('spatial-layers.update', $layer), [
            'map_type_id' => $jenis->id,
            'parent_id' => $layer->id,
            'name' => 'Layer',
        ])->assertSessionHasErrors('parent_id');
    }

    public function test_chained_cycle_through_descendant_is_rejected(): void
    {
        $admin = $this->admin();
        $jenis = $this->jenis();
        $root = SpatialLayer::create(['slug' => 'root', 'name' => 'Root', 'title' => 'Root', 'map_type_id' => $jenis->id]);
        $child = SpatialLayer::create(['slug' => 'child', 'name' => 'Child', 'title' => 'Child', 'map_type_id' => $jenis->id, 'parent_id' => $root->id]);

        // Coba jadikan root sebagai anak dari child-nya sendiri -> cycle berantai.
        $this->actingAs($admin)->put(route('spatial-layers.update', $root), [
            'map_type_id' => $jenis->id,
            'parent_id' => $child->id,
            'name' => 'Root',
        ])->assertSessionHasErrors('parent_id');
    }

    public function test_destroy_blocked_when_layer_has_children(): void
    {
        $admin = $this->admin();
        $jenis = $this->jenis();
        $root = SpatialLayer::create(['slug' => 'root', 'name' => 'Root', 'title' => 'Root', 'map_type_id' => $jenis->id]);
        SpatialLayer::create(['slug' => 'child', 'name' => 'Child', 'title' => 'Child', 'map_type_id' => $jenis->id, 'parent_id' => $root->id]);

        $this->actingAs($admin)->delete(route('spatial-layers.destroy', $root))->assertRedirect();

        $this->assertDatabaseHas('spatial_layers', ['id' => $root->id]);
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

        $this->actingAs($admin)->get(route('spatial-layers.create'))->assertOk();
    }

    /**
     * Regresi: halaman edit terpisah sudah dihapus — mengubah Informasi Layer kini
     * lewat modal di halaman detail (pola sama seperti modal edit Kategori di
     * /dashboard/categories), form modal-nya tetap kirim ke route update yang sama.
     */
    public function test_show_page_has_edit_modal_instead_of_separate_edit_page(): void
    {
        $admin = $this->admin();
        $jenis = $this->jenis();
        $root = SpatialLayer::create(['slug' => 'root-'.uniqid(), 'name' => 'Root', 'title' => 'Root', 'map_type_id' => $jenis->id]);
        SpatialLayer::create(['slug' => 'child-'.uniqid(), 'name' => 'Child', 'title' => 'Child', 'map_type_id' => $jenis->id, 'parent_id' => $root->id]);

        $response = $this->actingAs($admin)->get(route('spatial-layers.show', $root));

        $response->assertOk()
            ->assertSee('id="editLayerModal"', false)
            ->assertSee(route('spatial-layers.update', $root), false)
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
        $jenis = $this->jenis();
        $layer = SpatialLayer::create(['slug' => 'layer-'.uniqid(), 'name' => 'Layer Modal Uji', 'title' => 'Layer Modal Uji', 'map_type_id' => $jenis->id, 'color' => '#28a745', 'is_marker' => true, 'icon' => 'mdi mdi-road']);

        $response = $this->actingAs($admin)->get(route('spatial-layers.show', $layer));

        $response->assertOk()
            ->assertSee('class="color-picker-widget"', false)
            ->assertSee('id="layer_edit_colorSwatches"', false)
            ->assertSee('class="icon-picker-grid" id="layer_edit_iconGrid"', false)
            ->assertSee('id="layer_edit_iconSearch"', false)
            ->assertSee('class="settings-switch-group"', false)
            ->assertSee('name="map_type_id"', false)
            ->assertSee('name="parent_id"', false)
            ->assertSee('btn-gradient-warning', false);
    }

    public function test_index_page_renders_tree(): void
    {
        $admin = $this->admin();
        $jenis = $this->jenis();
        SpatialLayer::create(['slug' => 'root-'.uniqid(), 'name' => 'Root Tree Uji', 'title' => 'Root', 'map_type_id' => $jenis->id]);

        $this->actingAs($admin)->get(route('spatial-layers.index'))->assertOk()->assertSee('Root Tree Uji');
    }

    /**
     * Regresi: "Tambah Layer" di index sekarang modal (pola sama dengan modal
     * Tambah Kategori di categories/index.blade.php), bukan lagi link ke halaman
     * penuh /spatial-layers/create — tombolnya membuka #addLayerModal, dan form
     * di dalam modal itu POST langsung ke spatial-layers.store.
     */
    public function test_index_page_has_add_layer_modal_instead_of_create_page_link(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get(route('spatial-layers.index'));

        $response->assertOk()
            ->assertSee('id="addLayerModal"', false)
            ->assertSee('data-bs-target="#addLayerModal"', false)
            ->assertSee('id="addLayerForm"', false)
            ->assertSee('action="'.route('spatial-layers.store').'"', false)
            ->assertDontSee('href="'.route('spatial-layers.create').'"', false);
    }

    /**
     * Regresi: kalau submit modal Tambah Layer gagal validasi, halaman index
     * harus otomatis membuka ulang modalnya (bukan diam-diam gagal tanpa ada
     * indikasi ke user) — pola sama dengan modal edit Layer di show.blade.php.
     */
    public function test_index_page_reopens_add_layer_modal_after_validation_error(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)
            ->from(route('spatial-layers.index'))
            ->post(route('spatial-layers.store'), ['name' => 'Tanpa Jenis']);

        $response->assertRedirect(route('spatial-layers.index'))->assertSessionHasErrors('map_type_id');

        $followUp = $this->actingAs($admin)->get(route('spatial-layers.index'));
        $followUp->assertOk()
            ->assertSee("document.getElementById('addLayerModal')", false)
            ->assertSee('value="Tanpa Jenis"', false);
    }

    /**
     * Regresi: tampilan index disamakan dengan categories/index.blade.php (stats
     * card, badge Jenis, badge jumlah sub-layer/data) — pastikan elemen visual baru
     * ini benar-benar muncul, bukan cuma "halaman tidak error".
     */
    public function test_index_page_shows_stats_cards_and_jenis_badge(): void
    {
        $admin = $this->admin();
        $jenis = $this->jenis();
        $root = SpatialLayer::create(['slug' => 'root-'.uniqid(), 'name' => 'Layer Stats Uji', 'title' => 'Layer Stats Uji', 'map_type_id' => $jenis->id]);
        SpatialLayer::create(['slug' => 'child-'.uniqid(), 'name' => 'Child Stats Uji', 'title' => 'Child', 'map_type_id' => $jenis->id, 'parent_id' => $root->id]);

        $response = $this->actingAs($admin)->get(route('spatial-layers.index'));

        $response->assertOk()
            ->assertSee('Layer Akar')
            ->assertSee('Sub Layer')
            ->assertSee('Marker Aktif')
            ->assertSee($jenis->nama)
            ->assertSee('1 sub', false);
    }

    /**
     * Regresi: tabel index digabung (Warna/Tipe/Icon -> 1 kolom Style), kolom
     * Status dihapus, dan tombol detail (mata) ditambahkan di kolom Aksi.
     */
    public function test_index_table_has_no_status_column_and_links_to_detail_page(): void
    {
        $admin = $this->admin();
        $jenis = $this->jenis();
        $layer = SpatialLayer::create(['slug' => 'layer-'.uniqid(), 'name' => 'Layer Kolom Uji', 'title' => 'Layer Kolom Uji', 'map_type_id' => $jenis->id]);

        $response = $this->actingAs($admin)->get(route('spatial-layers.index'));

        $response->assertOk()
            ->assertDontSee('<th>Status</th>', false)
            ->assertDontSee('<th>Warna</th>', false)
            ->assertDontSee('<th>Icon</th>', false)
            ->assertSee('>Style</th>', false)
            ->assertSee(route('spatial-layers.show', $layer), false);
    }

    public function test_show_page_renders_layer_detail(): void
    {
        $admin = $this->admin();
        $jenis = $this->jenis();
        $parent = SpatialLayer::create(['slug' => 'parent-'.uniqid(), 'name' => 'Parent Detail Uji', 'title' => 'Parent', 'map_type_id' => $jenis->id]);
        $child = SpatialLayer::create(['slug' => 'child-'.uniqid(), 'name' => 'Child Detail Uji', 'title' => 'Child', 'map_type_id' => $jenis->id, 'parent_id' => $parent->id]);

        $response = $this->actingAs($admin)->get(route('spatial-layers.show', $child));

        $response->assertOk()
            ->assertSee('Child Detail Uji')
            ->assertSee('Parent Detail Uji')
            ->assertSee($jenis->nama);
    }

    /**
     * Regresi: halaman detail Layer perlu card peta (collapsible, basemap-only)
     * dan tabel Data Spasial bergaya sama seperti /dashboard/data-spatial?type=tematik.
     */
    /**
     * Regresi: kartu "Peta Data Spasial" dan kartu tabel "Data Spasial" digabung
     * jadi SATU kartu dengan switcher Tabel/Peta (pola sama dengan tombol
     * Tabel/Peta di data_spatial/index.blade.php), bukan dua kartu terpisah lagi.
     */
    public function test_show_page_renders_single_card_with_table_map_switcher(): void
    {
        $admin = $this->admin();
        $jenis = $this->jenis();
        $layer = SpatialLayer::create(['slug' => 'layer-'.uniqid(), 'name' => 'Layer Peta Uji', 'title' => 'Layer Peta Uji', 'map_type_id' => $jenis->id]);

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
            ->assertSee('<th>Metadata</th>', false)
            ->assertDontSee('id="layerMapCollapse"', false);
    }

    /**
     * Regresi: card "Layer Anak" di samping Informasi Layer dihapus, card Informasi
     * Layer jadi collapsible (default collapsed sama seperti card peta), tabel Data
     * Spasial dapat search box dan tombol hapus di kolom Aksi.
     */
    public function test_show_page_has_collapsible_info_card_search_and_delete_button(): void
    {
        $admin = $this->admin();
        $jenis = $this->jenis();
        $layer = SpatialLayer::create(['slug' => 'layer-'.uniqid(), 'name' => 'Layer Aksi Uji', 'title' => 'Layer Aksi Uji', 'map_type_id' => $jenis->id]);
        $feature = SpatialLayerFeature::create([
            'spatial_layer_id' => $layer->id,
            'external_id' => 'FTR-001',
            'geometry' => DB::raw("ST_GeomFromText('POINT(127.8 1.5)')"),
        ]);

        $response = $this->actingAs($admin)->get(route('spatial-layers.show', $layer));

        $response->assertOk()
            ->assertDontSee('Layer Anak')
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
        $jenis = $this->jenis();
        $layer = SpatialLayer::create(['slug' => 'layer-'.uniqid(), 'name' => 'Layer Tambah Uji', 'title' => 'Layer Tambah Uji', 'map_type_id' => $jenis->id]);

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
     * dengan tabel Daftar Layer di spatial-layers/index.blade.php), dan dapat
     * filter tambahan "Status Metadata" (Lengkap/Belum Lengkap).
     */
    public function test_show_page_data_spasial_table_has_pagination_and_status_filter(): void
    {
        $admin = $this->admin();
        $jenis = $this->jenis();
        $layer = SpatialLayer::create(['slug' => 'layer-'.uniqid(), 'name' => 'Layer DataTable Uji', 'title' => 'Layer DataTable Uji', 'map_type_id' => $jenis->id]);
        $lengkap = SpatialLayerFeature::create([
            'spatial_layer_id' => $layer->id,
            'geometry' => DB::raw("ST_GeomFromText('POINT(127.8 1.5)')"),
            'metadata_dinamis' => ['sumber_data' => 'Uji'],
        ]);
        $belum = SpatialLayerFeature::create([
            'spatial_layer_id' => $layer->id,
            'geometry' => DB::raw("ST_GeomFromText('POINT(127.9 1.6)')"),
        ]);

        $response = $this->actingAs($admin)->get(route('spatial-layers.show', $layer));

        $response->assertOk()
            ->assertSee('id="dataSpasialStatusFilter"', false)
            ->assertSee('Metadata Lengkap')
            ->assertSee('Metadata Belum Lengkap')
            ->assertSee('data-status="lengkap"', false)
            ->assertSee('data-status="belum"', false);
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
        $jenis = $this->jenis();
        $layer = SpatialLayer::create(['slug' => 'layer-'.uniqid(), 'name' => 'Layer Detail Modal Uji', 'title' => 'Layer Detail Modal Uji', 'map_type_id' => $jenis->id]);
        $feature = SpatialLayerFeature::create([
            'spatial_layer_id' => $layer->id,
            'external_id' => 'KODE-001',
            'geometry' => DB::raw("ST_GeomFromText('POINT(127.8 1.5)')"),
            'metadata_dinamis' => ['sumber_data' => 'Uji'],
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
        $jenis = $this->jenis();
        $layer = SpatialLayer::create(['slug' => 'layer-'.uniqid(), 'name' => 'Layer Kosong DataTable Uji', 'title' => 'Layer Kosong DataTable Uji', 'map_type_id' => $jenis->id]);

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
     * (Jenis Peta, Sub Layer, Data Spasial, Status) di atas kartu Informasi Layer.
     */
    public function test_show_page_displays_breadcrumb_and_stats_cards(): void
    {
        $admin = $this->admin();
        $jenis = $this->jenis();
        $root = SpatialLayer::create(['slug' => 'root-'.uniqid(), 'name' => 'Root Stats Uji', 'title' => 'Root', 'map_type_id' => $jenis->id]);
        SpatialLayer::create(['slug' => 'child-'.uniqid(), 'name' => 'Child Stats Uji', 'title' => 'Child', 'map_type_id' => $jenis->id, 'parent_id' => $root->id]);
        SpatialLayerFeature::create(['spatial_layer_id' => $root->id, 'geometry' => DB::raw("ST_GeomFromText('POINT(127.8 1.5)')")]);

        $response = $this->actingAs($admin)->get(route('spatial-layers.show', $root));

        $response->assertOk()
            ->assertSee('breadcrumb', false)
            ->assertSee('Daftar Layer & Data', false)
            ->assertSee('Sub Layer')
            ->assertSee('Data Spasial')
            ->assertSee('class="stat-value">1</h3>', false);
    }

    /**
     * Regresi: tombol Hapus Layer di halaman detail cuma muncul kalau Layer tidak
     * punya anak maupun Data Spasial (konsisten dengan validasi di
     * SpatialLayerController::destroy() yang menolak hapus kalau masih dipakai).
     */
    public function test_show_page_hides_delete_button_when_layer_has_children_or_features(): void
    {
        $admin = $this->admin();
        $jenis = $this->jenis();
        $root = SpatialLayer::create(['slug' => 'root-'.uniqid(), 'name' => 'Root Hapus Uji', 'title' => 'Root', 'map_type_id' => $jenis->id]);
        SpatialLayer::create(['slug' => 'child-'.uniqid(), 'name' => 'Child Hapus Uji', 'title' => 'Child', 'map_type_id' => $jenis->id, 'parent_id' => $root->id]);

        $response = $this->actingAs($admin)->get(route('spatial-layers.show', $root));

        // Catatan: route('spatial-layers.destroy', ...) dan route('spatial-layers.update', ...)
        // menghasilkan URL yang SAMA (beda cuma method HTTP-nya) — form edit yang
        // selalu ada bikin assertDontSee(url) false-negative, jadi di sini cek
        // title tombol Hapus Layer yang unik, bukan URL-nya.
        $response->assertOk()->assertDontSee('title="Hapus Layer"', false);
    }

    public function test_show_page_shows_delete_button_when_layer_is_empty(): void
    {
        $admin = $this->admin();
        $jenis = $this->jenis();
        $layer = SpatialLayer::create(['slug' => 'layer-'.uniqid(), 'name' => 'Layer Kosong Uji', 'title' => 'Layer', 'map_type_id' => $jenis->id]);

        $response = $this->actingAs($admin)->get(route('spatial-layers.show', $layer));

        $response->assertOk()->assertSee('title="Hapus Layer"', false);
    }

    public function test_user_without_permission_cannot_view_detail_page(): void
    {
        $jenis = $this->jenis();
        $layer = SpatialLayer::create(['slug' => 'layer-'.uniqid(), 'name' => 'Layer Uji', 'title' => 'Layer Uji', 'map_type_id' => $jenis->id]);
        $user = User::factory()->create(['role_id' => Role::create(['name' => 'Admin OPD', 'slug' => 'admin-opd', 'description' => null])->id]);

        $this->actingAs($user)->get(route('spatial-layers.show', $layer))->assertForbidden();
    }

    public function test_index_table_has_no_gambar_column(): void
    {
        $admin = $this->admin();
        $jenis = $this->jenis();
        SpatialLayer::create(['slug' => 'layer-'.uniqid(), 'name' => 'Layer Kolom Gambar Uji', 'title' => 'Layer', 'map_type_id' => $jenis->id]);

        $this->actingAs($admin)->get(route('spatial-layers.index'))
            ->assertOk()
            ->assertDontSee('<th>Gambar</th>', false);
    }

    /**
     * Regresi: filter dropdown "Jenis Peta" di toolbar index, dan atribut
     * data-map-type-id per baris yang dipakai custom search DataTables.
     */
    public function test_index_page_shows_map_type_filter_dropdown(): void
    {
        $admin = $this->admin();
        $jenis = $this->jenis();
        $layer = SpatialLayer::create(['slug' => 'layer-'.uniqid(), 'name' => 'Layer Filter Uji', 'title' => 'Layer', 'map_type_id' => $jenis->id]);

        $response = $this->actingAs($admin)->get(route('spatial-layers.index'));

        $response->assertOk()
            ->assertSee('id="mapTypeFilter"', false)
            ->assertSee('<option value="'.$jenis->id.'">'.$jenis->nama.'</option>', false)
            ->assertSee('data-map-type-id="'.$jenis->id.'"', false);

        $this->assertNotNull($layer->fresh());
    }

    /**
     * Regresi: bulk update Jenis Peta (referensi pola "Ubah Kategori/Layer" di
     * halaman Data Spasial, docs/marimoi v2/04_implementation/
     * 12-implementasi-perbaikan-pemetaan.md Bagian 3.2).
     */
    public function test_admin_can_bulk_update_map_type_for_selected_layers(): void
    {
        $admin = $this->admin();
        $jenis = $this->jenis();
        $targetJenis = MapType::where('slug', 'psd')->firstOrFail();

        $layerA = SpatialLayer::create(['slug' => 'layer-a-'.uniqid(), 'name' => 'Layer A', 'title' => 'Layer A', 'map_type_id' => $jenis->id]);
        $layerB = SpatialLayer::create(['slug' => 'layer-b-'.uniqid(), 'name' => 'Layer B', 'title' => 'Layer B', 'map_type_id' => $jenis->id]);
        $untouched = SpatialLayer::create(['slug' => 'layer-c-'.uniqid(), 'name' => 'Layer C', 'title' => 'Layer C', 'map_type_id' => $jenis->id]);

        $response = $this->actingAs($admin)->put(route('spatial-layers.bulk-update-map-type'), [
            'ids' => [$layerA->id, $layerB->id],
            'map_type_id' => $targetJenis->id,
        ]);

        $response->assertRedirect(route('spatial-layers.index'));
        $this->assertEquals($targetJenis->id, $layerA->fresh()->map_type_id);
        $this->assertEquals($targetJenis->id, $layerB->fresh()->map_type_id);
        $this->assertEquals($jenis->id, $untouched->fresh()->map_type_id);
    }

    public function test_bulk_update_map_type_requires_ids_and_valid_map_type(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('spatial-layers.bulk-update-map-type'), [
            'ids' => [],
            'map_type_id' => '',
        ])->assertSessionHasErrors(['ids', 'map_type_id']);
    }

    public function test_user_without_edit_permission_cannot_bulk_update_map_type(): void
    {
        $jenis = $this->jenis();
        $targetJenis = MapType::where('slug', 'psd')->firstOrFail();
        $layer = SpatialLayer::create(['slug' => 'layer-'.uniqid(), 'name' => 'Layer Permission Uji', 'title' => 'Layer', 'map_type_id' => $jenis->id]);

        $role = Role::create(['name' => 'Viewer Layer', 'slug' => 'viewer-layer', 'description' => null]);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'spatial-layers.view', 'guard_name' => 'web']));
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->put(route('spatial-layers.bulk-update-map-type'), [
            'ids' => [$layer->id],
            'map_type_id' => $targetJenis->id,
        ])->assertForbidden();

        $this->assertEquals($jenis->id, $layer->fresh()->map_type_id);
    }
}
