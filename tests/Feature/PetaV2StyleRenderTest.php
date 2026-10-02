<?php

namespace Tests\Feature;

use App\Models\MapType;
use App\Models\MetadataDefinition;
use App\Models\SpatialLayer;
use App\Models\SpatialLayerFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regresi untuk perluasan /peta-v2 (docs/marimoi v2/04_implementation/
 * 12-implementasi-perbaikan-pemetaan.md Bagian 5) — icon/opacity di layer tree,
 * metadata_dinamis berlabel + gambar di detail feature.
 *
 * Disesuaikan ke skema v3 (plan mellow-weaving-eclipse Fase 3): icon pindah
 * ke layer_styles.definition (bukan kolom langsung), attributes+metadata_dinamis
 * tergabung jadi `properties`.
 */
class PetaV2StyleRenderTest extends TestCase
{
    use RefreshDatabase;

    private function categoryId(): string
    {
        return DB::table('categories_v3')->insertGetId([
            'id' => (string) Str::uuid(),
            'code' => 'cat-'.Str::random(8),
            'name' => 'Kategori Uji',
            'slug' => 'kategori-uji-'.Str::random(6),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id');
    }

    public function test_layer_tree_includes_icon_and_opacity(): void
    {
        $layer = SpatialLayer::create([
            'category_id' => $this->categoryId(),
            'layer_type_id' => 4,
            'code' => 'layer-uji',
            'slug' => 'layer-uji',
            'name' => 'Layer Uji',
            'status' => 'published',
            'published_at' => now(),
            'default_opacity' => 0.7,
        ]);
        $style = $layer->styles()->create([
            'name' => 'Default',
            'style_type' => 'simple',
            'is_default' => true,
            'definition' => ['color' => '#2563eb', 'icon' => 'mdi mdi-road', 'is_marker' => true, 'opacity' => 0.7],
        ]);
        $layer->update(['default_style_id' => $style->id]);

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
            'category_id' => $this->categoryId(),
            'layer_type_id' => 4,
            'code' => 'layer-uji',
            'slug' => 'layer-uji',
            'name' => 'Layer Uji',
            'map_type_id' => $jenis->id,
        ]);
        $feature = SpatialLayerFeature::create([
            'layer_id' => $layer->id,
            'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)'),
            'properties' => ['pagu' => '5000000'],
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
        $layer = SpatialLayer::create([
            'category_id' => $this->categoryId(),
            'layer_type_id' => 4,
            'code' => 'layer-uji',
            'slug' => 'layer-uji',
            'name' => 'Layer Uji',
        ]);
        $feature = SpatialLayerFeature::create([
            'layer_id' => $layer->id,
            'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)'),
        ]);

        $response = $this->getJson("/peta-v2/feature/{$feature->id}");

        $response->assertOk();
        $response->assertJsonPath('metadata_dinamis', []);
    }
}
