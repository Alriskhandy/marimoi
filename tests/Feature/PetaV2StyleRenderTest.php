<?php

namespace Tests\Feature;

use App\Models\MapType;
use App\Models\MetadataDefinition;
use App\Models\SpatialLayer;
use App\Models\SpatialLayerFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regresi untuk perluasan /peta-v2 (docs/marimoi v2/04_implementation/
 * 12-implementasi-perbaikan-pemetaan.md Bagian 5) — icon/opacity di layer tree,
 * metadata_dinamis berlabel + gambar di detail feature.
 */
class PetaV2StyleRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_layer_tree_includes_icon_and_opacity(): void
    {
        SpatialLayer::create([
            'slug' => 'layer-uji', 'name' => 'Layer Uji', 'title' => 'Layer Uji',
            'is_active' => true, 'icon' => 'mdi mdi-road', 'opacity' => 0.7,
        ]);

        $response = $this->getJson('/peta-v2/layers');

        $response->assertOk();
        $response->assertJsonFragment(['icon' => 'mdi mdi-road', 'opacity' => 0.7]);
    }

    public function test_feature_detail_includes_labeled_metadata_dinamis(): void
    {
        $jenis = MapType::where('slug', 'tematik')->firstOrFail();
        $pagu = MetadataDefinition::where('kode', 'pagu')->firstOrFail();
        $jenis->dynamicAttributes()->create(['metadata_definition_id' => $pagu->id]);
        $layer = SpatialLayer::create([
            'slug' => 'layer-uji', 'name' => 'Layer Uji', 'title' => 'Layer Uji',
            'map_type_id' => $jenis->id,
        ]);
        $feature = SpatialLayerFeature::create([
            'spatial_layer_id' => $layer->id,
            'geometry' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)'),
            'metadata_dinamis' => ['pagu' => '5000000'],
            'gambar' => 'images/spatial-layer-features/uji.jpg',
        ]);

        $response = $this->getJson("/peta-v2/feature/{$feature->id}");

        $response->assertOk();
        $response->assertJsonPath('metadata_dinamis.0.label', 'Pagu');
        $response->assertJsonPath('metadata_dinamis.0.satuan', 'Rp');
        $response->assertJsonPath('metadata_dinamis.0.value', '5000000');
        $response->assertJsonPath('gambar', 'images/spatial-layer-features/uji.jpg');
    }

    public function test_feature_detail_returns_empty_metadata_dinamis_when_none_stored(): void
    {
        $layer = SpatialLayer::create(['slug' => 'layer-uji', 'name' => 'Layer Uji', 'title' => 'Layer Uji']);
        $feature = SpatialLayerFeature::create([
            'spatial_layer_id' => $layer->id,
            'geometry' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)'),
        ]);

        $response = $this->getJson("/peta-v2/feature/{$feature->id}");

        $response->assertOk();
        $response->assertJsonPath('metadata_dinamis', []);
    }
}
