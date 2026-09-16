<?php

namespace Tests\Feature;

use App\Models\SharedMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_create_a_shared_map_link(): void
    {
        $response = $this->postJson(route('tematik.share.store'), [
            'layers' => ['Kesehatan', 'Batas Administrasi'],
            'viewport' => ['lat' => 0.78, 'lng' => 127.38, 'zoom' => 8],
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure(['slug', 'url']);

        $this->assertDatabaseHas('shared_maps', [
            'slug' => $response->json('slug'),
            'data_type' => 'tematik',
        ]);

        $sharedMap = SharedMap::first();
        $this->assertSame(['Kesehatan', 'Batas Administrasi'], $sharedMap->layers);
        $this->assertSame(0.78, $sharedMap->viewport['lat']);
    }

    public function test_creating_shared_map_requires_at_least_one_layer(): void
    {
        $response = $this->postJson(route('tematik.share.store'), [
            'layers' => [],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('layers');
    }

    public function test_guest_can_open_a_valid_shared_map_link(): void
    {
        $sharedMap = SharedMap::create([
            'slug' => 'abc12345',
            'layers' => ['Kesehatan'],
            'viewport' => ['lat' => 0.78, 'lng' => 127.38, 'zoom' => 8],
            'data_type' => 'tematik',
        ]);

        $response = $this->get(route('tematik.share.show', $sharedMap->slug));

        $response->assertOk();
        $response->assertSee('MARIMOI_SHARED_STATE', false);
        $response->assertSee('Kesehatan', false);
        $response->assertSee('127.38', false);
    }

    public function test_invalid_slug_falls_back_to_default_map_with_error_message(): void
    {
        $response = $this->get(route('tematik.share.show', 'does-not-exist'));

        $response->assertOk();
        $response->assertSee('MARIMOI_SHARE_ERROR', false);
        $response->assertSee('tidak valid', false);
    }

    public function test_expired_shared_map_is_treated_as_invalid(): void
    {
        $sharedMap = SharedMap::create([
            'slug' => 'expired1',
            'layers' => ['Kesehatan'],
            'data_type' => 'tematik',
            'expired_at' => now()->subDay(),
        ]);

        $response = $this->get(route('tematik.share.show', $sharedMap->slug));

        $response->assertOk();
        $response->assertSee('MARIMOI_SHARE_ERROR', false);
    }
}
