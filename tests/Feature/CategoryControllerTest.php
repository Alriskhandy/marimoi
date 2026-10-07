<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regresi untuk CategoryController — skema v3 (plan mellow-weaving-eclipse,
 * Fase 3 lanjutan). `Category` dibaca dari view `categories_tree_v3`
 * (gabungan categories_v3 root + category_nodes turunannya) tapi menulis
 * langsung ke tabel yang benar lewat override create()/update()/delete() di
 * model — test ini mengunci hirarki 3 level (root/child/grandchild) tetap
 * jalan persis seperti versi lama (flat `categories`), hanya tabel
 * sumbernya beda.
 *
 * Sejak 2026-10-06, kategori tidak lagi punya `type`/`is_active`/`icon`/
 * `color`/`gambar`/`is_marker` sendiri (lihat migration
 * drop_display_and_type_columns_from_categories_v3_and_category_nodes) —
 * aturan bisnis yang dulu bergantung padanya (tipe sama dengan parent,
 * maksimal 10 aktif per tipe) ikut dibuang bersama kolomnya.
 *
 * Hanya method yang benar2 diroutekan yang diuji (index/store/update/destroy/
 * getOptions) — lihat routes/backend.php & docblock CategoryController.
 *
 * store()/update() di index.blade.php dipanggil lewat jQuery $.ajax(), yang
 * otomatis mengirim header X-Requested-With: XMLHttpRequest (dibaca
 * Request::ajax() di controller) — postJson()/putJson() bawaan Laravel TIDAK
 * mengirim header itu, jadi ditambahkan manual lewat ajaxHeaders() supaya
 * jalur respons JSON yang sama dengan produksi yang teruji di sini.
 */
class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'description' => null]);
        foreach (['categories.view', 'categories.create', 'categories.edit', 'categories.delete'] as $perm) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']));
        }

        return User::factory()->create(['role_id' => $role->id]);
    }

    /**
     * @return array<string, string>
     */
    private function ajaxHeaders(): array
    {
        return ['X-Requested-With' => 'XMLHttpRequest'];
    }

    public function test_admin_can_create_a_root_category(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->postJson(route('categories.store'), [
            'nama' => 'Fasilitas Kesehatan',
        ], $this->ajaxHeaders());

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('categories_v3', ['name' => 'Fasilitas Kesehatan']);
    }

    public function test_admin_can_create_a_child_category_under_a_root(): void
    {
        $admin = $this->admin();
        $root = Category::create(['nama' => 'Root Uji']);

        $response = $this->actingAs($admin)->postJson(route('categories.store'), [
            'nama' => 'Child Uji',
            'parent_id' => $root->id,
        ], $this->ajaxHeaders());

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('category_nodes', ['name' => 'Child Uji', 'category_id' => $root->id, 'depth' => 1]);
    }

    public function test_grandchild_category_is_allowed_but_great_grandchild_is_rejected(): void
    {
        $admin = $this->admin();
        $root = Category::create(['nama' => 'Root Uji']);
        $child = Category::create(['nama' => 'Child Uji', 'parent_id' => $root->id]);

        $grandchildResponse = $this->actingAs($admin)->postJson(route('categories.store'), [
            'nama' => 'Grandchild Uji',
            'parent_id' => $child->id,
        ], $this->ajaxHeaders());
        $grandchildResponse->assertOk();
        $grandchild = Category::where('nama', 'Grandchild Uji')->firstOrFail();

        $tooDeepResponse = $this->actingAs($admin)->postJson(route('categories.store'), [
            'nama' => 'Terlalu Dalam',
            'parent_id' => $grandchild->id,
        ], $this->ajaxHeaders());

        $tooDeepResponse->assertStatus(422);
        $tooDeepResponse->assertJsonValidationErrors('parent_id');
    }

    public function test_admin_can_update_a_category(): void
    {
        $admin = $this->admin();
        $category = Category::create(['nama' => 'Nama Lama']);

        $response = $this->actingAs($admin)->putJson(route('categories.update', $category->id), [
            'nama' => 'Nama Baru',
        ], $this->ajaxHeaders());

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('categories_v3', ['id' => $category->id, 'name' => 'Nama Baru']);
    }

    public function test_updating_a_child_node_preserves_its_depth_and_category(): void
    {
        $admin = $this->admin();
        $root = Category::create(['nama' => 'Root Uji']);
        $child = Category::create(['nama' => 'Child Uji', 'parent_id' => $root->id]);

        $response = $this->actingAs($admin)->putJson(route('categories.update', $child->id), [
            'nama' => 'Child Diubah',
            'parent_id' => $root->id,
        ], $this->ajaxHeaders());

        $response->assertOk();
        $this->assertDatabaseHas('category_nodes', ['id' => $child->id, 'name' => 'Child Diubah', 'depth' => 1]);
    }

    public function test_destroy_is_blocked_when_category_has_children(): void
    {
        $admin = $this->admin();
        $root = Category::create(['nama' => 'Root Uji']);
        Category::create(['nama' => 'Child Uji', 'parent_id' => $root->id]);

        $response = $this->actingAs($admin)->delete(route('categories.destroy', $root->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('categories_v3', ['id' => $root->id]);
    }

    public function test_destroy_removes_a_leaf_category(): void
    {
        $admin = $this->admin();
        $category = Category::create(['nama' => 'Hapus Uji']);

        $response = $this->actingAs($admin)->delete(route('categories.destroy', $category->id));

        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseMissing('categories_v3', ['id' => $category->id]);
    }

    public function test_get_options_returns_three_level_hierarchy(): void
    {
        $admin = $this->admin();
        $root = Category::create(['nama' => 'Root Uji']);
        $child = Category::create(['nama' => 'Child Uji', 'parent_id' => $root->id]);
        Category::create(['nama' => 'Grandchild Uji', 'parent_id' => $child->id]);

        $response = $this->actingAs($admin)->getJson(route('categories.api.options'));

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(3, $data);
        $this->assertSame(0, $data[0]['level']);
        $this->assertSame(1, $data[1]['level']);
        $this->assertSame(2, $data[2]['level']);
    }

    public function test_index_page_renders_with_nested_categories(): void
    {
        $admin = $this->admin();
        $root = Category::create(['nama' => 'Root Uji']);
        Category::create(['nama' => 'Child Uji', 'parent_id' => $root->id]);

        $response = $this->actingAs($admin)->get(route('categories.index'));

        $response->assertOk()->assertSee('Root Uji')->assertSee('Child Uji');
    }

    public function test_user_without_permission_cannot_view_categories(): void
    {
        $role = Role::create(['name' => 'Admin OPD', 'slug' => 'admin-opd', 'description' => null]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get(route('categories.index'))->assertForbidden();
    }

    /**
     * Fase H butir 1 (spec-admin-manajemen-peta.md §5.1 butir 2) — memindahkan
     * subkategori ke induk lain harus memperbarui category_id/depth/path
     * SELURUH keturunannya, bukan cuma parent_id node yang dipindah sendiri
     * (bug yang ditemukan: field ini sebelumnya ditinggal basi).
     */
    public function test_moving_a_node_cascades_category_and_depth_to_descendants(): void
    {
        $admin = $this->admin();
        $rootA = Category::create(['nama' => 'Root A']);
        $rootB = Category::create(['nama' => 'Root B']);
        $child = Category::create(['nama' => 'Child', 'parent_id' => $rootA->id]);
        $grandchild = Category::create(['nama' => 'Grandchild', 'parent_id' => $child->id]);

        $this->actingAs($admin)->putJson(route('categories.update', $child->id), [
            'nama' => 'Child',
            'parent_id' => $rootB->id,
        ], $this->ajaxHeaders())->assertOk();

        $childRow = DB::table('category_nodes')->where('id', $child->id)->first();
        $this->assertSame($rootB->id, $childRow->category_id);
        $this->assertSame(1, $childRow->depth);

        $grandchildRow = DB::table('category_nodes')->where('id', $grandchild->id)->first();
        $this->assertSame($rootB->id, $grandchildRow->category_id);
        $this->assertSame(2, $grandchildRow->depth);
        $this->assertStringStartsWith((string) $childRow->path, (string) $grandchildRow->path);
    }

    public function test_sort_order_can_be_set_on_create_and_update(): void
    {
        $admin = $this->admin();
        $category = Category::create(['nama' => 'Kategori', 'sort_order' => 0]);
        $this->assertDatabaseHas('categories_v3', ['id' => $category->id, 'sort_order' => 0]);

        $this->actingAs($admin)->putJson(route('categories.update', $category->id), [
            'nama' => 'Kategori',
            'sort_order' => 7,
        ], $this->ajaxHeaders())->assertOk();

        $this->assertDatabaseHas('categories_v3', ['id' => $category->id, 'sort_order' => 7]);
    }

    public function test_sort_order_controls_display_order_over_alphabetical(): void
    {
        $admin = $this->admin();
        Category::create(['nama' => 'Zebra', 'sort_order' => 1]);
        Category::create(['nama' => 'Apple', 'sort_order' => 2]);

        $html = $this->actingAs($admin)->get(route('categories.index'))->getContent();

        $this->assertLessThan(strpos($html, 'Apple'), strpos($html, 'Zebra'));
    }

    /**
     * Fase H butir 4 (spec-admin-manajemen-peta.md §5.1 butir 5) — pesan
     * hapus-aman harus menyebut jumlah & nama penghalang, bukan generik.
     */
    public function test_destroy_blocked_message_names_blocking_children(): void
    {
        $admin = $this->admin();
        $root = Category::create(['nama' => 'Root Uji']);
        Category::create(['nama' => 'Anak Satu', 'parent_id' => $root->id]);

        $response = $this->actingAs($admin)->delete(route('categories.destroy', $root->id));

        $response->assertRedirect();
        $response->assertSessionHas('error', fn ($message) => str_contains($message, 'Anak Satu') && str_contains($message, '1 sub-kategori'));
    }

    public function test_destroy_blocked_message_names_blocking_layers(): void
    {
        $admin = $this->admin();
        $category = Category::create(['nama' => 'Kategori Dipakai']);
        DB::table('layers')->insert([
            'id' => (string) Str::uuid(),
            'category_id' => $category->id,
            'layer_type_id' => 4,
            'code' => 'layer-'.Str::random(8),
            'name' => 'Layer Uji',
            'slug' => 'layer-uji-'.Str::random(6),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->delete(route('categories.destroy', $category->id));

        $response->assertRedirect();
        $response->assertSessionHas('error', fn ($message) => str_contains($message, 'Layer Uji') && str_contains($message, '1 Layer'));
    }

    /**
     * Regresi: `layers` pakai SoftDeletes (SpatialLayer) — menghapus Layer
     * tidak membuang barisnya, cuma mengisi `deleted_at`. hasLinkedLayers()/
     * blockedByLayersMessage() query lewat DB::table('layers') langsung
     * (bukan Eloquent), yang TIDAK otomatis menyaring baris soft-deleted,
     * jadi Kategori/Subkategori yang Layer-nya sudah dihapus tetap dianggap
     * "masih dipakai" selamanya kalau tidak disaring manual.
     */
    public function test_destroy_is_allowed_when_only_linked_layer_is_soft_deleted(): void
    {
        $admin = $this->admin();
        $category = Category::create(['nama' => 'Kategori Layer Terhapus']);
        DB::table('layers')->insert([
            'id' => (string) Str::uuid(),
            'category_id' => $category->id,
            'layer_type_id' => 4,
            'code' => 'layer-'.Str::random(8),
            'name' => 'Layer Sudah Dihapus',
            'slug' => 'layer-sudah-dihapus-'.Str::random(6),
            'deleted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->delete(route('categories.destroy', $category->id));

        $response->assertRedirect(route('categories.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('categories_v3', ['id' => $category->id]);
    }

    public function test_destroy_is_still_blocked_by_a_layer_that_is_not_soft_deleted(): void
    {
        $admin = $this->admin();
        $category = Category::create(['nama' => 'Kategori Layer Aktif']);
        DB::table('layers')->insert([
            'id' => (string) Str::uuid(),
            'category_id' => $category->id,
            'layer_type_id' => 4,
            'code' => 'layer-'.Str::random(8),
            'name' => 'Layer Aktif',
            'slug' => 'layer-aktif-'.Str::random(6),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->delete(route('categories.destroy', $category->id));

        $response->assertRedirect();
        $response->assertSessionHas('error', fn ($message) => str_contains($message, 'Layer Aktif'));
        $this->assertDatabaseHas('categories_v3', ['id' => $category->id]);
    }

    /**
     * Fase H butir 3 (spec-admin-manajemen-peta.md §5.1 butir 4) — "panel isi
     * katalog": link dari halaman Kategori langsung ke Daftar Layer yang
     * sudah terfilter ke kategori ini.
     */
    public function test_index_page_has_link_to_filtered_layer_list(): void
    {
        $admin = $this->admin();
        $category = Category::create(['nama' => 'Kategori Uji']);

        $response = $this->actingAs($admin)->get(route('categories.index'));

        $response->assertOk()->assertSee(route('spatial-layers.index', ['category_id' => $category->id]), false);
    }
}
