<?php

namespace Tests\Feature;

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
            'name' => 'Kategori Uji',
            'slug' => 'kategori-uji-'.Str::random(6),
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

    /**
     * Sejak `layers.map_type_id` dihapus (2026-10-06, lihat migration
     * drop_map_type_id_and_visibility_from_layers_table), Layer tidak lagi
     * bisa punya atribut dinamis aktif — endpoint ini SELALU mengembalikan
     * `metadata_dinamis` kosong untuk SEMUA Layer, bukan cuma saat memang
     * tidak ada yang tersimpan. Menggantikan
     * test_feature_detail_includes_labeled_metadata_dinamis yang menguji
     * perilaku lama (label dari MapTypeDynamicAttribute) — perilaku itu
     * sengaja sudah tidak bisa terjadi lagi, bukan regresi.
     */
    public function test_feature_detail_always_returns_empty_metadata_dinamis(): void
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
            'gambar' => 'images/spatial-layer-features/uji.jpg',
        ]);

        $response = $this->getJson("/peta-v2/feature/{$feature->id}");

        $response->assertOk();
        $response->assertJsonPath('metadata_dinamis', []);
        $response->assertJsonPath('gambar', 'images/spatial-layer-features/uji.jpg');
    }
}
