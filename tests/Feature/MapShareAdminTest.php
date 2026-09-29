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
 * Regresi untuk generate/revoke link berbagi admin (docs/marimoi v2/04_implementation/
 * 11-plan-dashboard-skema-baru.md Bagian D.1).
 */
class MapShareAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'description' => null]);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'maps.manage', 'guard_name' => 'web']));

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function publishedMap(): Map
    {
        $layer = SpatialLayer::create(['slug' => 'layer-'.uniqid(), 'name' => 'Layer', 'title' => 'Layer', 'layer_class' => 'thematic']);
        $map = Map::create(['slug' => 'peta-uji-'.uniqid(), 'title' => 'Peta Uji']);
        MapLayer::create(['map_id' => $map->id, 'spatial_layer_id' => $layer->id, 'display_order' => 0]);
        $map->publish();

        return $map;
    }

    public function test_generating_share_creates_a_usable_token(): void
    {
        $admin = $this->admin();
        $map = $this->publishedMap();
        $publication = $map->publications()->first();

        $this->actingAs($admin)
            ->post(route('maps.publications.share', [$map, $publication]))
            ->assertRedirect(route('maps.edit', $map))
            ->assertSessionHas('shareToken');

        $this->assertSame(1, $publication->shares()->count());
    }

    public function test_share_token_is_never_stored_as_plaintext(): void
    {
        $admin = $this->admin();
        $map = $this->publishedMap();
        $publication = $map->publications()->first();

        $response = $this->actingAs($admin)->post(route('maps.publications.share', [$map, $publication]));
        $token = $response->getSession()->get('shareToken');

        $share = $publication->shares()->first();
        $this->assertNotSame($token, $share->token_hash);
        $this->assertSame(hash('sha256', $token), $share->token_hash);
    }

    public function test_revoking_share_makes_it_invalid(): void
    {
        $admin = $this->admin();
        $map = $this->publishedMap();
        $publication = $map->publications()->first();
        ['share' => $share] = MapShare::generateFor($publication, $admin);

        $this->actingAs($admin)->delete(route('maps.shares.revoke', [$map, $share]))
            ->assertRedirect(route('maps.edit', $map));

        $this->assertFalse($share->fresh()->isValid());
    }

    public function test_share_from_another_map_cannot_be_revoked_via_this_map(): void
    {
        $admin = $this->admin();
        $mapA = $this->publishedMap();
        $mapB = $this->publishedMap();
        ['share' => $share] = MapShare::generateFor($mapA->publications()->first(), $admin);

        $this->actingAs($admin)->delete(route('maps.shares.revoke', [$mapB, $share]))->assertNotFound();
    }
}
