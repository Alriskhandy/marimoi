<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SpatialLayer;
use App\Models\SpatialLayerFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Filter Peta Tematik membaca feature V3 (`spatial_features.properties`) milik Layer
 * published — sama dengan properti yang diekspos `/geojson` dan disaring di browser.
 */
class FrontendFilterOptionsTest extends TestCase
{
    use RefreshDatabase;

    private function layer(string $name, string $status = 'published'): SpatialLayer
    {
        $category = Category::create(['nama' => 'Kategori '.$name]);

        return SpatialLayer::create([
            'category_id' => $category->id,
            'layer_type_id' => 4,
            'code' => 'layer-'.Str::random(8),
            'slug' => 'layer-'.Str::random(8),
            'name' => $name,
            'status' => $status,
            'published_at' => $status === 'published' ? now() : null,
        ]);
    }

    private function feature(SpatialLayer $layer, array $properties): void
    {
        SpatialLayerFeature::create([
            'layer_id' => $layer->id,
            'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)'),
            'properties' => $properties,
        ]);
    }

    public function test_filter_options_endpoint_returns_distinct_values_without_needing_any_layer_active(): void
    {
        $layer = $this->layer('Fasilitas Uji');
        $this->feature($layer, ['KABUPATEN' => 'Kota Ternate', 'tahun' => '2025', 'opd_penanggung_jawab' => 'Dinas Uji Coba']);
        $this->feature($layer, ['KABUPATEN' => 'Kota Ternate', 'tahun' => '2024']);

        $response = $this->getJson('/geojson/filter-options?type=tematik');

        $response->assertOk();
        $response->assertJson([
            'kabupaten' => ['Kota Ternate'],
            'opd_pengelola' => ['Dinas Uji Coba'],
        ]);
        $this->assertEqualsCanonicalizing([2024, 2025], $response->json('tahun'));
    }

    public function test_filter_options_ignore_features_of_draft_layers(): void
    {
        $this->feature($this->layer('Layer Draft', 'draft'), ['KABUPATEN' => 'Kota Rahasia', 'tahun' => '2030']);

        $this->getJson('/geojson/filter-options?type=tematik')
            ->assertOk()
            ->assertJson(['kabupaten' => [], 'tahun' => [], 'opd_pengelola' => []]);
    }

    public function test_filter_categories_endpoint_returns_only_categories_matching_kabupaten(): void
    {
        $this->feature($this->layer('Layer Ternate'), ['KABUPATEN' => 'Kota Ternate']);
        $this->feature($this->layer('Layer Tidore'), ['KABUPATEN' => 'Kota Tidore']);
        $this->feature($this->layer('Layer Draft Ternate', 'draft'), ['KABUPATEN' => 'Kota Ternate']);

        $response = $this->getJson('/geojson/filter-categories?type=tematik&kabupaten='.urlencode('Kota Ternate'));

        $response->assertOk();
        $response->assertExactJson(['categories' => ['Layer Ternate']]);
    }

    public function test_filter_categories_endpoint_combines_kabupaten_tahun_and_opd_with_and_logic(): void
    {
        $this->feature($this->layer('Layer Gabungan'), [
            'KABUPATEN' => 'Kota Ternate',
            'tahun' => '2025',
            'opd_penanggung_jawab' => 'Dinas Uji Coba',
        ]);

        $matching = $this->getJson(
            '/geojson/filter-categories?type=tematik&kabupaten='.urlencode('Kota Ternate').'&tahun=2025&opd_pengelola='.urlencode('Dinas Uji Coba')
        );
        $matching->assertOk()->assertJson(['categories' => ['Layer Gabungan']]);

        $notMatching = $this->getJson(
            '/geojson/filter-categories?type=tematik&kabupaten='.urlencode('Kota Ternate').'&tahun=2020'
        );
        $notMatching->assertOk()->assertJson(['categories' => []]);
    }
}
