<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresi untuk CategoryController — skema v3 (plan mellow-weaving-eclipse,
 * Fase 3 lanjutan). `Category` dibaca dari view `categories_tree_v3`
 * (gabungan categories_v3 root + category_nodes turunannya) tapi menulis
 * langsung ke tabel yang benar lewat override create()/update()/delete() di
 * model — test ini mengunci hirarki 3 level (root/child/grandchild) & aturan
 * bisnis (tipe sama dengan parent, maksimal 10 aktif per tipe) tetap jalan
 * persis seperti versi lama (flat `categories`), hanya tabel sumbernya beda.
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
            'type' => 'tematik',
            'nama' => 'Fasilitas Kesehatan',
            'warna' => '#ff0000',
            'icon' => 'hospital',
            'is_active' => true,
        ], $this->ajaxHeaders());

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('categories_v3', ['name' => 'Fasilitas Kesehatan', 'type' => 'tematik']);
    }

    public function test_admin_can_create_a_child_category_under_a_root(): void
    {
        $admin = $this->admin();
        $root = Category::create(['type' => 'tematik', 'nama' => 'Root Uji', 'is_active' => true]);

        $response = $this->actingAs($admin)->postJson(route('categories.store'), [
            'type' => 'tematik',
            'nama' => 'Child Uji',
            'parent_id' => $root->id,
        ], $this->ajaxHeaders());

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('category_nodes', ['name' => 'Child Uji', 'category_id' => $root->id, 'depth' => 1]);
    }

    public function test_grandchild_category_is_allowed_but_great_grandchild_is_rejected(): void
    {
        $admin = $this->admin();
        $root = Category::create(['type' => 'tematik', 'nama' => 'Root Uji']);
        $child = Category::create(['type' => 'tematik', 'nama' => 'Child Uji', 'parent_id' => $root->id]);

        $grandchildResponse = $this->actingAs($admin)->postJson(route('categories.store'), [
            'type' => 'tematik',
            'nama' => 'Grandchild Uji',
            'parent_id' => $child->id,
        ], $this->ajaxHeaders());
        $grandchildResponse->assertOk();
        $grandchild = Category::where('nama', 'Grandchild Uji')->firstOrFail();

        $tooDeepResponse = $this->actingAs($admin)->postJson(route('categories.store'), [
            'type' => 'tematik',
            'nama' => 'Terlalu Dalam',
            'parent_id' => $grandchild->id,
        ], $this->ajaxHeaders());

        $tooDeepResponse->assertStatus(422);
        $tooDeepResponse->assertJsonValidationErrors('parent_id');
    }

    public function test_parent_must_have_same_type(): void
    {
        $admin = $this->admin();
        $root = Category::create(['type' => 'tematik', 'nama' => 'Root Tematik']);

        $response = $this->actingAs($admin)->postJson(route('categories.store'), [
            'type' => 'psd',
            'nama' => 'Anak Beda Tipe',
            'parent_id' => $root->id,
        ], $this->ajaxHeaders());

        $response->assertStatus(422)->assertJsonValidationErrors('parent_id');
    }

    public function test_only_ten_active_categories_allowed_per_type(): void
    {
        $admin = $this->admin();
        for ($i = 0; $i < 10; $i++) {
            Category::create(['type' => 'tematik', 'nama' => "Aktif {$i}", 'is_active' => true]);
        }

        $response = $this->actingAs($admin)->postJson(route('categories.store'), [
            'type' => 'tematik',
            'nama' => 'Kelebihan Batas',
            'is_active' => true,
        ], $this->ajaxHeaders());

        $response->assertStatus(422)->assertJsonValidationErrors('is_active');
    }

    public function test_admin_can_update_a_category(): void
    {
        $admin = $this->admin();
        $category = Category::create(['type' => 'tematik', 'nama' => 'Nama Lama', 'warna' => '#000000']);

        $response = $this->actingAs($admin)->putJson(route('categories.update', $category->id), [
            'type' => 'tematik',
            'nama' => 'Nama Baru',
            'warna' => '#ffffff',
        ], $this->ajaxHeaders());

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('categories_v3', ['id' => $category->id, 'name' => 'Nama Baru', 'color' => '#ffffff']);
    }

    public function test_updating_a_child_node_preserves_its_depth_and_category(): void
    {
        $admin = $this->admin();
        $root = Category::create(['type' => 'tematik', 'nama' => 'Root Uji']);
        $child = Category::create(['type' => 'tematik', 'nama' => 'Child Uji', 'parent_id' => $root->id]);

        $response = $this->actingAs($admin)->putJson(route('categories.update', $child->id), [
            'type' => 'tematik',
            'nama' => 'Child Diubah',
            'parent_id' => $root->id,
        ], $this->ajaxHeaders());

        $response->assertOk();
        $this->assertDatabaseHas('category_nodes', ['id' => $child->id, 'name' => 'Child Diubah', 'depth' => 1]);
    }

    public function test_destroy_is_blocked_when_category_has_children(): void
    {
        $admin = $this->admin();
        $root = Category::create(['type' => 'tematik', 'nama' => 'Root Uji']);
        Category::create(['type' => 'tematik', 'nama' => 'Child Uji', 'parent_id' => $root->id]);

        $response = $this->actingAs($admin)->delete(route('categories.destroy', $root->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('categories_v3', ['id' => $root->id]);
    }

    public function test_destroy_removes_a_leaf_category(): void
    {
        $admin = $this->admin();
        $category = Category::create(['type' => 'tematik', 'nama' => 'Hapus Uji']);

        $response = $this->actingAs($admin)->delete(route('categories.destroy', $category->id));

        $response->assertRedirect(route('categories.index', ['type' => 'tematik']));
        $this->assertDatabaseMissing('categories_v3', ['id' => $category->id]);
    }

    public function test_get_options_returns_three_level_hierarchy(): void
    {
        $admin = $this->admin();
        $root = Category::create(['type' => 'tematik', 'nama' => 'Root Uji']);
        $child = Category::create(['type' => 'tematik', 'nama' => 'Child Uji', 'parent_id' => $root->id]);
        Category::create(['type' => 'tematik', 'nama' => 'Grandchild Uji', 'parent_id' => $child->id]);

        $response = $this->actingAs($admin)->getJson(route('categories.api.options', 'tematik'));

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
        $root = Category::create(['type' => 'tematik', 'nama' => 'Root Uji', 'is_active' => true]);
        Category::create(['type' => 'tematik', 'nama' => 'Child Uji', 'parent_id' => $root->id]);

        $response = $this->actingAs($admin)->get(route('categories.index', ['type' => 'tematik']));

        $response->assertOk()->assertSee('Root Uji')->assertSee('Child Uji');
    }

    public function test_user_without_permission_cannot_view_categories(): void
    {
        $role = Role::create(['name' => 'Admin OPD', 'slug' => 'admin-opd', 'description' => null]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get(route('categories.index'))->assertForbidden();
    }
}
