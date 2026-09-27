<?php

namespace Tests\Feature;

use App\Models\Map;
use App\Models\MapLayer;
use App\Models\MapShare;
use App\Models\SpatialLayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapSharingTest extends TestCase
{
    use RefreshDatabase;

    private function makeMapWithLayer(): Map
    {
        $layer = SpatialLayer::create(['slug' => 'jalan-peta', 'name' => 'Jalan', 'title' => 'Jalan', 'layer_class' => 'thematic']);

        $map = Map::create(['slug' => 'peta-uji', 'title' => 'Peta Uji']);

        MapLayer::create([
            'map_id' => $map->id,
            'spatial_layer_id' => $layer->id,
            'display_order' => 0,
            'opacity' => 0.8,
        ]);

        return $map;
    }

    public function test_map_gets_public_id_automatically(): void
    {
        $map = Map::create(['slug' => 'peta-tanpa-layer', 'title' => 'Peta Tanpa Layer']);

        $this->assertNotNull($map->public_id);
    }

    public function test_map_layer_belongs_to_map_and_spatial_layer(): void
    {
        $map = $this->makeMapWithLayer();

        $this->assertCount(1, $map->layers);
        $this->assertSame('Jalan', $map->layers->first()->spatialLayer->name);
    }

    public function test_publish_creates_first_current_publication_with_layer_snapshot(): void
    {
        $map = $this->makeMapWithLayer();

        $publication = $map->publish();

        $this->assertSame(1, $publication->revision);
        $this->assertTrue($publication->is_current);
        $this->assertCount(1, $publication->config_snapshot['layers']);
        $this->assertSame(0.8, $publication->config_snapshot['layers'][0]['opacity']);
    }

    public function test_publishing_again_increments_revision_and_demotes_previous(): void
    {
        $map = $this->makeMapWithLayer();
        $first = $map->publish();

        $second = $map->publish();

        $this->assertSame(2, $second->revision);
        $this->assertTrue($second->is_current);
        $this->assertFalse($first->fresh()->is_current);
        $this->assertNotNull($first->fresh()->unpublished_at);
    }

    public function test_generate_share_returns_plaintext_token_once_and_stores_only_hash(): void
    {
        $map = $this->makeMapWithLayer();
        $publication = $map->publish();

        ['share' => $share, 'token' => $token] = MapShare::generateFor($publication);

        $this->assertNotEmpty($token);
        $this->assertNotSame($token, $share->token_hash);
        $this->assertSame(hash('sha256', $token), $share->token_hash);
    }

    public function test_share_can_be_found_by_its_plaintext_token(): void
    {
        $map = $this->makeMapWithLayer();
        $publication = $map->publish();
        ['share' => $share, 'token' => $token] = MapShare::generateFor($publication);

        $found = MapShare::findByToken($token);

        $this->assertTrue($found->is($share));
        $this->assertNull(MapShare::findByToken('token-salah'));
    }

    public function test_share_is_valid_until_revoked(): void
    {
        $map = $this->makeMapWithLayer();
        $publication = $map->publish();
        ['share' => $share] = MapShare::generateFor($publication);

        $this->assertTrue($share->isValid());

        $share->revoke();

        $this->assertFalse($share->fresh()->isValid());
    }

    public function test_share_with_expires_at_in_the_past_is_invalid(): void
    {
        $map = $this->makeMapWithLayer();
        $publication = $map->publish();
        ['share' => $share] = MapShare::generateFor($publication, expiresAt: now()->subDay());

        $this->assertFalse($share->fresh()->isValid());
    }

    public function test_record_access_increments_counter_and_logs_entry(): void
    {
        $map = $this->makeMapWithLayer();
        $publication = $map->publish();
        ['share' => $share] = MapShare::generateFor($publication);

        $share->recordAccess(ipHash: 'abc123', userAgent: 'PHPUnit');
        $share->recordAccess(ipHash: 'abc123', userAgent: 'PHPUnit');

        $this->assertSame(2, $share->fresh()->access_count);
        $this->assertCount(2, $share->fresh()->accesses);
        $this->assertNotNull($share->fresh()->last_accessed_at);
    }

    public function test_creator_is_recorded_when_provided(): void
    {
        $user = User::factory()->create();
        $map = $this->makeMapWithLayer();
        $publication = $map->publish();

        ['share' => $share] = MapShare::generateFor($publication, creator: $user);

        $this->assertTrue($share->creator->is($user));
    }
}
