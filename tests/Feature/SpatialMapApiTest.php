<?php

namespace Tests\Feature;

use App\Models\AdministrativeRegion;
use App\Models\Category;
use App\Models\DataSpatial;
use App\Models\SpatialLayer;
use App\Models\SpatialLayerFeature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regresi untuk /peta-v2 (docs/marimoi v2/04_implementation/10-plan-peta-skema-baru.md):
 * halaman & API baru yang membaca SpatialLayer/SpatialLayerFeature secara read-only,
 * berdampingan dengan /peta-tematik lama. Tidak menyentuh Category/DataSpatial kecuali
 * untuk join balik read-only di featureDetail().
 */
class SpatialMapApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeLayer(array $overrides = []): SpatialLayer
    {
        return SpatialLayer::create(array_merge([
            'slug' => 'layer-'.uniqid(),
            'name' => 'Layer Uji',
            'title' => 'Layer Uji',
            'color' => '#ff0000',
            'is_marker' => true,
            'is_active' => true,
        ], $overrides));
    }

    private function makeFeature(SpatialLayer $layer, array $overrides = []): SpatialLayerFeature
    {
        return SpatialLayerFeature::create(array_merge([
            'spatial_layer_id' => $layer->id,
            'geometry' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)'),
            'attributes' => ['NAMA' => 'Contoh'],
        ], $overrides));
    }

    public function test_peta_v2_page_loads(): void
    {
        $this->get('/peta-v2')->assertOk();
    }

    public function test_layer_tree_returns_only_active_root_layers(): void
    {
        $active = $this->makeLayer();
        $this->makeLayer(['is_active' => false]);

        $response = $this->getJson('/peta-v2/layers');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['id' => $active->id]);
    }

    public function test_layer_tree_includes_children(): void
    {
        $parent = $this->makeLayer();
        $child = $this->makeLayer(['parent_id' => $parent->id]);

        $response = $this->getJson('/peta-v2/layers');

        $response->assertOk();
        $response->assertJsonPath('0.children.0.id', $child->id);
    }

    public function test_geojson_endpoint_returns_feature_collection_for_layer(): void
    {
        $layer = $this->makeLayer();
        $this->makeFeature($layer);
        $this->makeFeature($layer);
        $otherLayer = $this->makeLayer();
        $this->makeFeature($otherLayer);

        $response = $this->getJson("/peta-v2/geojson/{$layer->slug}");

        $response->assertOk();
        $response->assertJsonPath('type', 'FeatureCollection');
        $this->assertCount(2, $response->json('features'));
    }

    public function test_geojson_includes_features_without_region(): void
    {
        $layer = $this->makeLayer();
        $feature = $this->makeFeature($layer, ['region_id' => null]);

        $response = $this->getJson("/peta-v2/geojson/{$layer->slug}");

        $response->assertOk();
        $response->assertJsonFragment(['id' => $feature->id, 'region_id' => null]);
    }

    public function test_feature_detail_includes_legacy_descriptive_fields_when_available(): void
    {
        $user = User::factory()->create();
        Category::create(['type' => 'tematik', 'nama' => 'Kategori Uji', 'warna' => '#000']);
        $legacy = DataSpatial::factory()->create([
            'user_id' => $user->id,
            'deskripsi' => 'Deskripsi legacy uji',
            'sumber_data' => 'Sumber Uji',
        ]);
        $layer = $this->makeLayer();
        $feature = $this->makeFeature($layer, ['legacy_data_spatial_id' => $legacy->id]);

        $response = $this->getJson("/peta-v2/feature/{$feature->id}");

        $response->assertOk();
        $response->assertJsonPath('legacy.deskripsi', 'Deskripsi legacy uji');
        $response->assertJsonPath('legacy.sumber_data', 'Sumber Uji');
    }

    public function test_feature_detail_returns_null_legacy_when_not_backfilled(): void
    {
        $layer = $this->makeLayer();
        $feature = $this->makeFeature($layer, ['legacy_data_spatial_id' => null]);

        $response = $this->getJson("/peta-v2/feature/{$feature->id}");

        $response->assertOk();
        $response->assertJsonPath('legacy', null);
    }

    public function test_feature_detail_includes_region_when_assigned(): void
    {
        $region = AdministrativeRegion::create([
            'code_kemendagri' => '82.01',
            'name' => 'Kabupaten Uji',
            'level' => 'kabupaten_kota',
        ]);
        $layer = $this->makeLayer();
        $feature = $this->makeFeature($layer, ['region_id' => $region->id]);

        $response = $this->getJson("/peta-v2/feature/{$feature->id}");

        $response->assertOk();
        $response->assertJsonPath('region.name', 'Kabupaten Uji');
    }
}
