<?php

namespace Tests\Feature;

use App\Models\MapType;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SpatialLayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'layer_class' => 'thematic',
        ])->assertRedirect(route('spatial-layers.index'));

        $this->assertDatabaseHas('spatial_layers', ['name' => 'Jalan Provinsi', 'map_type_id' => $jenis->id]);
    }

    public function test_deeply_nested_tree_is_allowed(): void
    {
        $admin = $this->admin();
        $jenis = $this->jenis();
        $level1 = SpatialLayer::create(['slug' => 'l1', 'name' => 'Level 1', 'title' => 'Level 1', 'layer_class' => 'thematic', 'map_type_id' => $jenis->id]);
        $level2 = SpatialLayer::create(['slug' => 'l2', 'name' => 'Level 2', 'title' => 'Level 2', 'layer_class' => 'thematic', 'map_type_id' => $jenis->id, 'parent_id' => $level1->id]);
        $level3 = SpatialLayer::create(['slug' => 'l3', 'name' => 'Level 3', 'title' => 'Level 3', 'layer_class' => 'thematic', 'map_type_id' => $jenis->id, 'parent_id' => $level2->id]);

        $this->actingAs($admin)->post(route('spatial-layers.store'), [
            'map_type_id' => $jenis->id,
            'parent_id' => $level3->id,
            'name' => 'Level 4',
            'layer_class' => 'thematic',
        ])->assertRedirect(route('spatial-layers.index'));

        $level4 = SpatialLayer::where('name', 'Level 4')->firstOrFail();
        $this->assertSame($level3->id, $level4->parent_id);
    }

    public function test_direct_self_parent_cycle_is_rejected(): void
    {
        $admin = $this->admin();
        $jenis = $this->jenis();
        $layer = SpatialLayer::create(['slug' => 'l1', 'name' => 'Layer', 'title' => 'Layer', 'layer_class' => 'thematic', 'map_type_id' => $jenis->id]);

        $this->actingAs($admin)->put(route('spatial-layers.update', $layer), [
            'map_type_id' => $jenis->id,
            'parent_id' => $layer->id,
            'name' => 'Layer',
            'layer_class' => 'thematic',
        ])->assertSessionHasErrors('parent_id');
    }

    public function test_chained_cycle_through_descendant_is_rejected(): void
    {
        $admin = $this->admin();
        $jenis = $this->jenis();
        $root = SpatialLayer::create(['slug' => 'root', 'name' => 'Root', 'title' => 'Root', 'layer_class' => 'thematic', 'map_type_id' => $jenis->id]);
        $child = SpatialLayer::create(['slug' => 'child', 'name' => 'Child', 'title' => 'Child', 'layer_class' => 'thematic', 'map_type_id' => $jenis->id, 'parent_id' => $root->id]);

        // Coba jadikan root sebagai anak dari child-nya sendiri -> cycle berantai.
        $this->actingAs($admin)->put(route('spatial-layers.update', $root), [
            'map_type_id' => $jenis->id,
            'parent_id' => $child->id,
            'name' => 'Root',
            'layer_class' => 'thematic',
        ])->assertSessionHasErrors('parent_id');
    }

    public function test_destroy_blocked_when_layer_has_children(): void
    {
        $admin = $this->admin();
        $jenis = $this->jenis();
        $root = SpatialLayer::create(['slug' => 'root', 'name' => 'Root', 'title' => 'Root', 'layer_class' => 'thematic', 'map_type_id' => $jenis->id]);
        SpatialLayer::create(['slug' => 'child', 'name' => 'Child', 'title' => 'Child', 'layer_class' => 'thematic', 'map_type_id' => $jenis->id, 'parent_id' => $root->id]);

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

    public function test_edit_page_renders_with_children_dropdown_and_feature_list(): void
    {
        $admin = $this->admin();
        $jenis = $this->jenis();
        $root = SpatialLayer::create(['slug' => 'root-'.uniqid(), 'name' => 'Root', 'title' => 'Root', 'layer_class' => 'thematic', 'map_type_id' => $jenis->id]);
        SpatialLayer::create(['slug' => 'child-'.uniqid(), 'name' => 'Child', 'title' => 'Child', 'layer_class' => 'thematic', 'map_type_id' => $jenis->id, 'parent_id' => $root->id]);

        $this->actingAs($admin)->get(route('spatial-layers.edit', $root))->assertOk()->assertSee('Root');
    }

    public function test_index_page_renders_tree(): void
    {
        $admin = $this->admin();
        $jenis = $this->jenis();
        SpatialLayer::create(['slug' => 'root-'.uniqid(), 'name' => 'Root Tree Uji', 'title' => 'Root', 'layer_class' => 'thematic', 'map_type_id' => $jenis->id]);

        $this->actingAs($admin)->get(route('spatial-layers.index'))->assertOk()->assertSee('Root Tree Uji');
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
        $root = SpatialLayer::create(['slug' => 'root-'.uniqid(), 'name' => 'Layer Stats Uji', 'title' => 'Layer Stats Uji', 'layer_class' => 'thematic', 'map_type_id' => $jenis->id]);
        SpatialLayer::create(['slug' => 'child-'.uniqid(), 'name' => 'Child Stats Uji', 'title' => 'Child', 'layer_class' => 'thematic', 'map_type_id' => $jenis->id, 'parent_id' => $root->id]);

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
        $layer = SpatialLayer::create(['slug' => 'layer-'.uniqid(), 'name' => 'Layer Kolom Uji', 'title' => 'Layer Kolom Uji', 'layer_class' => 'thematic', 'map_type_id' => $jenis->id]);

        $response = $this->actingAs($admin)->get(route('spatial-layers.index'));

        $response->assertOk()
            ->assertDontSee('<th>Status</th>', false)
            ->assertDontSee('<th>Warna</th>', false)
            ->assertDontSee('<th>Icon</th>', false)
            ->assertSee('<th>Style</th>', false)
            ->assertSee(route('spatial-layers.show', $layer), false);
    }

    public function test_show_page_renders_layer_detail(): void
    {
        $admin = $this->admin();
        $jenis = $this->jenis();
        $parent = SpatialLayer::create(['slug' => 'parent-'.uniqid(), 'name' => 'Parent Detail Uji', 'title' => 'Parent', 'layer_class' => 'thematic', 'map_type_id' => $jenis->id]);
        $child = SpatialLayer::create(['slug' => 'child-'.uniqid(), 'name' => 'Child Detail Uji', 'title' => 'Child', 'layer_class' => 'thematic', 'map_type_id' => $jenis->id, 'parent_id' => $parent->id]);

        $response = $this->actingAs($admin)->get(route('spatial-layers.show', $child));

        $response->assertOk()
            ->assertSee('Child Detail Uji')
            ->assertSee('Parent Detail Uji')
            ->assertSee($jenis->nama);
    }

    public function test_user_without_permission_cannot_view_detail_page(): void
    {
        $jenis = $this->jenis();
        $layer = SpatialLayer::create(['slug' => 'layer-'.uniqid(), 'name' => 'Layer Uji', 'title' => 'Layer Uji', 'layer_class' => 'thematic', 'map_type_id' => $jenis->id]);
        $user = User::factory()->create(['role_id' => Role::create(['name' => 'Admin OPD', 'slug' => 'admin-opd', 'description' => null])->id]);

        $this->actingAs($user)->get(route('spatial-layers.show', $layer))->assertForbidden();
    }

    public function test_index_table_has_no_gambar_column(): void
    {
        $admin = $this->admin();
        $jenis = $this->jenis();
        SpatialLayer::create(['slug' => 'layer-'.uniqid(), 'name' => 'Layer Kolom Gambar Uji', 'title' => 'Layer', 'layer_class' => 'thematic', 'map_type_id' => $jenis->id]);

        $this->actingAs($admin)->get(route('spatial-layers.index'))
            ->assertOk()
            ->assertDontSee('<th>Gambar</th>', false);
    }
}
