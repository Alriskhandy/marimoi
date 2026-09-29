<?php

namespace Tests\Feature;

use App\Models\Map;
use App\Models\MapLayer;
use App\Models\MapShare;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SpatialLayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresi untuk Kelola Peta (docs/marimoi v2/04_implementation/
 * 11-plan-dashboard-skema-baru.md Bagian B).
 */
class MapManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'description' => null]);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'maps.manage', 'guard_name' => 'web']));

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function layer(array $overrides = []): SpatialLayer
    {
        return SpatialLayer::create(array_merge([
            'slug' => 'layer-'.uniqid(),
            'name' => 'Layer Uji',
            'title' => 'Layer Uji',
            'layer_class' => 'thematic',
            'is_active' => true,
        ], $overrides));
    }

    public function test_admin_can_create_a_map(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('maps.store'), [
            'title' => 'Peta Uji',
            'visibility' => 'private',
        ])->assertRedirect();

        $this->assertDatabaseHas('maps', ['title' => 'Peta Uji']);
    }

    public function test_user_without_permission_cannot_access_maps(): void
    {
        $role = Role::create(['name' => 'Admin OPD', 'slug' => 'admin-opd', 'description' => null]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get(route('maps.index'))->assertForbidden();
    }

    /**
     * Regresi: halaman ini perlu benar-benar dirender (GET) dengan data yang
     * mengisi tiap cabang tampilan (layer, publikasi, share) — bukan cuma dites
     * lewat aksi POST/PUT — supaya kesalahan kompilasi Blade ketahuan. Pola bug
     * yang sama sempat lolos di map-types/_form.blade.php.
     */
    public function test_create_page_renders(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('maps.create'))->assertOk();
    }

    public function test_edit_page_renders_with_layer_publication_and_share(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $map = Map::create(['slug' => 'peta-render-uji', 'title' => 'Peta Render Uji']);
        MapLayer::create(['map_id' => $map->id, 'spatial_layer_id' => $layer->id, 'display_order' => 0]);
        $publication = $map->publish($admin);
        MapShare::generateFor($publication, $admin);

        $this->actingAs($admin)->get(route('maps.edit', $map))
            ->assertOk()
            ->assertSee('Peta Render Uji')
            ->assertSee('Revisi #1');
    }

    public function test_attaching_layer_creates_map_layer_with_incrementing_order(): void
    {
        $admin = $this->admin();
        $map = Map::create(['slug' => 'peta-uji', 'title' => 'Peta Uji']);
        $layerA = $this->layer();
        $layerB = $this->layer();

        $this->actingAs($admin)->post(route('maps.layers.store', $map), ['spatial_layer_id' => $layerA->id]);
        $this->actingAs($admin)->post(route('maps.layers.store', $map), ['spatial_layer_id' => $layerB->id]);

        $this->assertSame(2, $map->layers()->count());
        $orders = $map->layers()->orderBy('display_order')->pluck('spatial_layer_id')->all();
        $this->assertSame([$layerA->id, $layerB->id], $orders);
    }

    public function test_attaching_the_same_layer_twice_is_rejected(): void
    {
        $admin = $this->admin();
        $map = Map::create(['slug' => 'peta-uji', 'title' => 'Peta Uji']);
        $layer = $this->layer();

        $this->actingAs($admin)->post(route('maps.layers.store', $map), ['spatial_layer_id' => $layer->id]);
        $this->actingAs($admin)->post(route('maps.layers.store', $map), ['spatial_layer_id' => $layer->id]);

        $this->assertSame(1, $map->layers()->count());
    }

    public function test_reorder_updates_display_order_according_to_payload(): void
    {
        $admin = $this->admin();
        $map = Map::create(['slug' => 'peta-uji', 'title' => 'Peta Uji']);
        $layerA = $this->layer();
        $layerB = $this->layer();
        $mapLayerA = MapLayer::create(['map_id' => $map->id, 'spatial_layer_id' => $layerA->id, 'display_order' => 0]);
        $mapLayerB = MapLayer::create(['map_id' => $map->id, 'spatial_layer_id' => $layerB->id, 'display_order' => 1]);

        $this->actingAs($admin)->postJson(route('maps.layers.reorder', $map), [
            'order' => [$mapLayerB->id, $mapLayerA->id],
        ])->assertOk();

        $this->assertSame(0, $mapLayerB->fresh()->display_order);
        $this->assertSame(1, $mapLayerA->fresh()->display_order);
    }

    public function test_opacity_outside_valid_range_is_rejected(): void
    {
        $admin = $this->admin();
        $map = Map::create(['slug' => 'peta-uji', 'title' => 'Peta Uji']);
        $layer = $this->layer();
        $mapLayer = MapLayer::create(['map_id' => $map->id, 'spatial_layer_id' => $layer->id, 'display_order' => 0]);

        $this->actingAs($admin)->put(route('maps.layers.update', [$map, $mapLayer]), ['opacity' => 1.5])
            ->assertSessionHasErrors('opacity');
    }

    public function test_removing_a_layer_deletes_map_layer_but_keeps_spatial_layer(): void
    {
        $admin = $this->admin();
        $map = Map::create(['slug' => 'peta-uji', 'title' => 'Peta Uji']);
        $layer = $this->layer();
        $mapLayer = MapLayer::create(['map_id' => $map->id, 'spatial_layer_id' => $layer->id, 'display_order' => 0]);

        $this->actingAs($admin)->delete(route('maps.layers.destroy', [$map, $mapLayer]));

        $this->assertDatabaseMissing('map_layers', ['id' => $mapLayer->id]);
        $this->assertDatabaseHas('spatial_layers', ['id' => $layer->id]);
    }
}
