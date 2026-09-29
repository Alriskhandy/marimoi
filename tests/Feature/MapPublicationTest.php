<?php

namespace Tests\Feature;

use App\Models\Map;
use App\Models\MapLayer;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SpatialLayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresi untuk publikasi peta (docs/marimoi v2/04_implementation/
 * 11-plan-dashboard-skema-baru.md Bagian C).
 */
class MapPublicationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'description' => null]);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'maps.manage', 'guard_name' => 'web']));

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function mapWithLayer(): Map
    {
        $layer = SpatialLayer::create(['slug' => 'layer-'.uniqid(), 'name' => 'Layer', 'title' => 'Layer', 'layer_class' => 'thematic']);
        $map = Map::create(['slug' => 'peta-uji-'.uniqid(), 'title' => 'Peta Uji']);
        MapLayer::create(['map_id' => $map->id, 'spatial_layer_id' => $layer->id, 'display_order' => 0]);

        return $map;
    }

    public function test_cannot_publish_a_map_without_layers(): void
    {
        $admin = $this->admin();
        $map = Map::create(['slug' => 'peta-kosong', 'title' => 'Peta Kosong']);

        $this->actingAs($admin)->post(route('maps.publish', $map))->assertSessionHas('error');

        $this->assertSame(0, $map->publications()->count());
    }

    public function test_first_publish_creates_revision_one_as_current(): void
    {
        $admin = $this->admin();
        $map = $this->mapWithLayer();

        $this->actingAs($admin)->post(route('maps.publish', $map))->assertRedirect(route('maps.edit', $map));

        $publication = $map->publications()->first();
        $this->assertSame(1, $publication->revision);
        $this->assertTrue($publication->is_current);
    }

    public function test_second_publish_creates_revision_two_and_demotes_revision_one(): void
    {
        $admin = $this->admin();
        $map = $this->mapWithLayer();

        $this->actingAs($admin)->post(route('maps.publish', $map));
        $this->actingAs($admin)->post(route('maps.publish', $map));

        $this->assertSame(2, $map->publications()->count());
        $this->assertFalse($map->publications()->where('revision', 1)->first()->is_current);
        $this->assertTrue($map->publications()->where('revision', 2)->first()->is_current);
    }

    public function test_snapshot_contains_the_layer_present_at_publish_time(): void
    {
        $admin = $this->admin();
        $map = $this->mapWithLayer();

        $this->actingAs($admin)->post(route('maps.publish', $map));

        $publication = $map->publications()->first();
        $this->assertCount(1, $publication->config_snapshot['layers']);
    }
}
