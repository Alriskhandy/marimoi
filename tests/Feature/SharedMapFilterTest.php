<?php

namespace Tests\Feature;

use App\Models\SharedMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedMapFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_shared_map_stores_active_filters(): void
    {
        $response = $this->postJson(route('tematik.share.store'), [
            'layers' => ['Kesehatan'],
            'viewport' => ['lat' => 0.78, 'lng' => 127.38, 'zoom' => 8],
            'filters' => [
                'kabupaten' => 'Kota Ternate',
                'tahun' => 2025,
                'opd_pengelola' => 'DINKES',
            ],
        ]);

        $response->assertOk();

        $sharedMap = SharedMap::first();
        $this->assertSame([
            'kabupaten' => 'Kota Ternate',
            'tahun' => 2025,
            'opd_pengelola' => 'DINKES',
        ], $sharedMap->filters);
    }

    public function test_creating_shared_map_without_filters_still_succeeds(): void
    {
        $response = $this->postJson(route('tematik.share.store'), [
            'layers' => ['Kesehatan'],
        ]);

        $response->assertOk();

        $sharedMap = SharedMap::first();
        $this->assertNull($sharedMap->filters);
    }

    public function test_opening_shared_map_link_exposes_stored_filters_to_the_frontend(): void
    {
        $sharedMap = SharedMap::create([
            'slug' => 'filtertest',
            'layers' => ['Kesehatan'],
            'data_type' => 'tematik',
            'filters' => ['kabupaten' => 'Kota Ternate', 'tahun' => 2025, 'opd_pengelola' => 'DINKES'],
        ]);

        $response = $this->get(route('tematik.share.show', $sharedMap->slug));

        $response->assertOk();
        $response->assertSee('MARIMOI_SHARED_STATE', false);
        $response->assertSee('Kota Ternate', false);
        $response->assertSee('DINKES', false);
    }

    public function test_non_integer_tahun_filter_is_rejected(): void
    {
        $response = $this->postJson(route('tematik.share.store'), [
            'layers' => ['Kesehatan'],
            'filters' => ['tahun' => 'bukan-angka'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('filters.tahun');
    }
}
