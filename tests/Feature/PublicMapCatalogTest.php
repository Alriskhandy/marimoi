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
 * Katalog Peta Interaktif publik dibaca dari skema V3: satu mapset = satu Layer published,
 * dikelompokkan Kategori › Node (lihat App\Support\PublicMapCatalog).
 */
class PublicMapCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function layer(Category $parent, string $name, string $status = 'published', ?string $color = null): SpatialLayer
    {
        $nodeCategoryId = DB::table('category_nodes')->where('id', $parent->id)->value('category_id');
        $categoryId = $nodeCategoryId ?? $parent->id;
        $nodeId = $nodeCategoryId ? $parent->id : null;

        $layer = SpatialLayer::create([
            'category_id' => $categoryId,
            'category_node_id' => $nodeId,
            'layer_type_id' => 4,
            'code' => 'layer-'.Str::random(8),
            'slug' => 'layer-'.Str::random(8),
            'name' => $name,
            'status' => $status,
            'published_at' => $status === 'published' ? now() : null,
        ]);

        if ($color) {
            $style = $layer->styles()->create([
                'name' => 'Default',
                'style_type' => 'simple',
                'is_default' => true,
                'definition' => ['color' => $color, 'icon' => 'fa fa-plane', 'is_marker' => true],
            ]);
            $layer->update(['default_style_id' => $style->id]);
        }

        return $layer;
    }

    private function feature(SpatialLayer $layer, array $properties = []): SpatialLayerFeature
    {
        return SpatialLayerFeature::create([
            'layer_id' => $layer->id,
            'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)'),
            'properties' => $properties,
        ]);
    }

    private function metadata(): array
    {
        return $this->getJson('/geojson?metadata_only=true&type=tematik')->assertOk()->json();
    }

    public function test_metadata_lists_published_layers_grouped_by_category_and_node_path(): void
    {
        $infrastruktur = Category::create(['nama' => 'Infrastruktur']);
        $transportasi = Category::create(['nama' => 'Transportasi', 'parent_id' => $infrastruktur->id]);
        $udara = Category::create(['nama' => 'Udara', 'parent_id' => $transportasi->id]);

        $bandara = $this->layer($udara, 'Bandara', 'published', '#123456');
        $this->feature($bandara);
        $this->feature($bandara);
        $this->layer($infrastruktur, 'Jalan Provinsi');

        $metadata = $this->metadata();
        $items = collect($metadata['all_categories'])->keyBy('nama');

        $this->assertNull($items['Infrastruktur']['parent_id']);
        $this->assertSame($items['Infrastruktur']['id'], $items['Transportasi › Udara']['parent_id']);
        $this->assertSame($items['Transportasi › Udara']['id'], $items['Bandara']['parent_id']);
        $this->assertSame($items['Infrastruktur']['id'], $items['Jalan Provinsi']['parent_id'], 'layer tanpa node langsung di bawah kategori');

        $this->assertSame('#123456', $items['Bandara']['warna']);
        $this->assertSame('fa fa-plane', $items['Bandara']['icon']);
        $this->assertTrue($items['Bandara']['is_marker']);

        $this->assertSame(2, $metadata['category_counts']['Bandara']);
        $this->assertSame(0, $metadata['category_counts']['Jalan Provinsi']);
        $this->assertArrayHasKey('Bandara', $metadata['category_versions']);
    }

    public function test_metadata_hides_draft_layers_and_categories_without_published_layers(): void
    {
        $tampil = Category::create(['nama' => 'Kategori Tampil']);
        $kosong = Category::create(['nama' => 'Kategori Draft Saja']);
        $this->layer($tampil, 'Layer Publik');
        $this->layer($tampil, 'Layer Draft', 'draft');
        $this->layer($kosong, 'Layer Draft Lain', 'draft');

        $names = collect($this->metadata()['all_categories'])->pluck('nama');

        $this->assertContains('Layer Publik', $names);
        $this->assertNotContains('Layer Draft', $names);
        $this->assertNotContains('Kategori Draft Saja', $names);
    }

    public function test_duplicate_layer_names_get_unique_names_shared_with_geojson_features(): void
    {
        $provinsi = Category::create(['nama' => 'Provinsi']);
        $kota = Category::create(['nama' => 'Kota']);
        $jalanProvinsi = $this->layer($provinsi, 'Jalan');
        $jalanKota = $this->layer($kota, 'Jalan');
        $featureProvinsi = $this->feature($jalanProvinsi);
        $this->feature($jalanKota);

        $leafNames = collect($this->metadata()['category_counts'])->keys();
        $this->assertEqualsCanonicalizing(['Jalan (Provinsi)', 'Jalan (Kota)'], $leafNames->all());

        $features = $this->getJson('/geojson?type=tematik&kategori[]='.urlencode('Jalan (Provinsi)'))
            ->assertOk()
            ->json('features');

        $this->assertCount(1, $features);
        $this->assertSame($featureProvinsi->id, $features[0]['properties']['id']);
        $this->assertSame('Jalan (Provinsi)', $features[0]['properties']['kategori']);
    }

    public function test_geojson_never_returns_features_of_draft_layers(): void
    {
        $kategori = Category::create(['nama' => 'Kategori Uji']);
        $publik = $this->feature($this->layer($kategori, 'Layer Publik'));
        $draft = $this->feature($this->layer($kategori, 'Layer Draft', 'draft'));

        $ids = collect($this->getJson('/geojson?type=tematik')->assertOk()->json('features'))->pluck('properties.id');

        $this->assertContains($publik->id, $ids);
        $this->assertNotContains($draft->id, $ids);
    }

    public function test_metadata_exposes_the_dashboard_style_of_each_mapset(): void
    {
        $kategori = Category::create(['nama' => 'Kategori Style']);
        $layer = $this->layer($kategori, 'Layer Bergaya');
        $style = $layer->styles()->create([
            'name' => 'Default',
            'style_type' => 'simple',
            'is_default' => true,
            'definition' => ['color' => '#ff0000', 'icon' => 'fa fa-home', 'is_marker' => true, 'opacity' => 0.6, 'size' => 9],
        ]);
        $layer->update(['default_style_id' => $style->id]);

        $leaf = collect($this->metadata()['all_categories'])->firstWhere('nama', 'Layer Bergaya');

        $this->assertSame([
            'type' => 'simple',
            'color' => '#ff0000',
            'icon' => 'fa fa-home',
            'is_marker' => true,
            'opacity' => 0.6,
            'size' => 9,
            'field' => null,
            'classes' => [],
        ], $leaf['style']);
    }

    public function test_metadata_exposes_classes_of_categorized_style(): void
    {
        $kategori = Category::create(['nama' => 'Kategori Kelas']);
        $layer = $this->layer($kategori, 'Layer Berkelas');
        $style = $layer->styles()->create([
            'name' => 'Per Jenis',
            'style_type' => 'categorized',
            'classification_field' => 'JENIS',
            'is_default' => true,
            'definition' => ['field' => 'JENIS', 'classes' => [['value' => 'Hutan', 'color' => '#00aa00', 'label' => 'Hutan']]],
        ]);
        $layer->update(['default_style_id' => $style->id]);

        $leaf = collect($this->metadata()['all_categories'])->firstWhere('nama', 'Layer Berkelas');

        $this->assertSame('categorized', $leaf['style']['type']);
        $this->assertSame('JENIS', $leaf['style']['field']);
        $this->assertSame('#00aa00', $leaf['style']['classes'][0]['color']);
    }

    public function test_geojson_features_carry_their_style_override(): void
    {
        $kategori = Category::create(['nama' => 'Kategori Override']);
        $layer = $this->layer($kategori, 'Layer Override');
        $custom = $this->feature($layer);
        $custom->update(['style_override' => ['color' => '#123abc', 'opacity' => 0.5, 'size' => 4, 'is_marker' => false, 'icon' => null]]);
        $plain = $this->feature($layer);

        $features = collect($this->getJson('/geojson?type=tematik&kategori[]='.urlencode('Layer Override'))
            ->assertOk()
            ->json('features'))->keyBy('properties.id');

        $this->assertSame('#123abc', $features[$custom->id]['properties']['style_override']['color']);
        $this->assertNull($features[$plain->id]['properties']['style_override']);
    }

    public function test_geojson_features_list_their_documentation_photos(): void
    {
        $kategori = Category::create(['nama' => 'Kategori Foto']);
        $layer = $this->layer($kategori, 'Layer Foto');
        $withPhoto = $this->feature($layer);
        $withPhoto->update(['gambar' => 'features/foto-uji.jpg']);
        $withoutPhoto = $this->feature($layer);

        $features = collect($this->getJson('/geojson?type=tematik&kategori[]='.urlencode('Layer Foto'))
            ->assertOk()
            ->json('features'))->keyBy('properties.id');

        $this->assertSame([asset('storage/features/foto-uji.jpg')], $features[$withPhoto->id]['properties']['gambar_list']);
        $this->assertSame(asset('storage/features/foto-uji.jpg'), $features[$withPhoto->id]['properties']['gambar']);
        $this->assertSame([], $features[$withoutPhoto->id]['properties']['gambar_list']);
    }

    public function test_geojson_feature_chunks_no_longer_carry_the_legacy_category_tree(): void
    {
        $kategori = Category::create(['nama' => 'Kategori Uji']);
        $this->feature($this->layer($kategori, 'Layer Publik'));

        $response = $this->getJson('/geojson?type=tematik&kategori[]='.urlencode('Layer Publik'))->assertOk();

        $response->assertJsonMissingPath('all_categories');
        $response->assertJsonMissingPath('root_categories');
    }
}
