<?php

namespace Tests\Feature;

use App\Models\Map;
use App\Models\MapLayer;
use App\Models\MapShare;
use App\Models\SpatialLayer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regresi untuk halaman publik buka link berbagi (docs/marimoi v2/04_implementation/
 * 11-plan-dashboard-skema-baru.md Bagian D.2).
 */
class PublicMapShareTest extends TestCase
{
    use RefreshDatabase;

    private function publishedMap(): Map
    {
        $layer = SpatialLayer::create(['slug' => 'layer-'.uniqid(), 'name' => 'Layer', 'title' => 'Layer', 'layer_class' => 'thematic', 'is_active' => true]);
        $map = Map::create(['slug' => 'peta-uji-'.uniqid(), 'title' => 'Peta Uji Publik']);
        MapLayer::create(['map_id' => $map->id, 'spatial_layer_id' => $layer->id, 'display_order' => 0]);
        $map->publish();

        return $map;
    }

    public function test_valid_link_shows_the_map_and_increments_access_count(): void
    {
        $map = $this->publishedMap();
        ['share' => $share, 'token' => $token] = MapShare::generateFor($map->publications()->first());

        $this->get(route('map-shares.show', $token))
            ->assertOk()
            ->assertSee('Peta Uji Publik');

        $this->assertSame(1, $share->fresh()->access_count);
    }

    public function test_revoked_link_returns_404(): void
    {
        $map = $this->publishedMap();
        ['share' => $share, 'token' => $token] = MapShare::generateFor($map->publications()->first());
        $share->revoke();

        $this->get(route('map-shares.show', $token))->assertNotFound();
    }

    public function test_expired_link_returns_404(): void
    {
        $map = $this->publishedMap();
        ['token' => $token] = MapShare::generateFor($map->publications()->first(), null, now()->subDay());

        $this->get(route('map-shares.show', $token))->assertNotFound();
    }

    public function test_unknown_token_returns_404_not_500(): void
    {
        $this->get(route('map-shares.show', Str::random(48)))->assertNotFound();
    }
}
