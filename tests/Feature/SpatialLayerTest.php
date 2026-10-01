<?php

namespace Tests\Feature;

use App\Models\MapType;
use App\Models\SpatialLayer;
use App\Models\SpatialLayerMetadata;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpatialLayerTest extends TestCase
{
    use RefreshDatabase;

    public function test_map_types_are_seeded_from_migration(): void
    {
        $this->assertSame(5, MapType::count());
        $this->assertTrue(MapType::active()->pluck('slug')->contains('tematik'));
        $this->assertTrue(MapType::where('slug', 'psn')->exists());
    }

    public function test_spatial_layer_belongs_to_map_type(): void
    {
        $tematik = MapType::where('slug', 'tematik')->firstOrFail();

        $layer = SpatialLayer::create([
            'slug' => 'jalan-uji',
            'name' => 'Jalan Uji',
            'title' => 'Jalan Uji',
            'map_type_id' => $tematik->id,
        ]);

        $this->assertNotNull($layer->public_id);
        $this->assertTrue($layer->mapType->is($tematik));
    }

    public function test_selectable_scope_excludes_group_layers(): void
    {
        SpatialLayer::create(['slug' => 'kelompok-a', 'name' => 'Kelompok A', 'title' => 'Kelompok A', 'is_group' => true]);
        $layer = SpatialLayer::create(['slug' => 'jalan-b', 'name' => 'Jalan B', 'title' => 'Jalan B', 'is_group' => false]);

        $selectable = SpatialLayer::selectable()->pluck('slug');

        $this->assertTrue($selectable->contains('jalan-b'));
        $this->assertFalse($selectable->contains('kelompok-a'));
        $this->assertTrue(SpatialLayer::groups()->pluck('slug')->contains('kelompok-a'));
    }

    public function test_parent_child_hierarchy_relation(): void
    {
        $parent = SpatialLayer::create(['slug' => 'induk', 'name' => 'Induk', 'title' => 'Induk', 'is_group' => true]);
        $child = SpatialLayer::create(['slug' => 'anak', 'name' => 'Anak', 'title' => 'Anak', 'parent_id' => $parent->id]);

        $this->assertTrue($child->parent->is($parent));
        $this->assertTrue($parent->children->pluck('id')->contains($child->id));
    }

    public function test_atribut_schema_builds_dynamic_validation_rules(): void
    {
        $layer = SpatialLayer::create([
            'slug' => 'jalan-skema',
            'name' => 'Jalan Skema',
            'title' => 'Jalan Skema',
            'atribut_schema' => [
                'version' => 1,
                'fields' => [
                    ['key' => 'lebar_jalan_m', 'label' => 'Lebar Jalan (m)', 'type' => 'number', 'required' => true],
                    ['key' => 'kondisi', 'label' => 'Kondisi', 'type' => 'select', 'required' => true, 'options' => ['baik', 'rusak']],
                ],
            ],
        ]);

        $rules = $layer->atributValidationRules();

        $this->assertSame('required|numeric', $rules['attributes.lebar_jalan_m']);
        $this->assertSame('required|in:baik,rusak', $rules['attributes.kondisi']);
    }

    public function test_layer_without_atribut_schema_has_no_dynamic_rules(): void
    {
        $layer = SpatialLayer::create(['slug' => 'freeform', 'name' => 'Freeform', 'title' => 'Freeform']);

        $this->assertSame([], $layer->atributValidationRules());
    }

    public function test_spatial_layer_metadata_is_one_to_one(): void
    {
        $layer = SpatialLayer::create(['slug' => 'jalan-meta', 'name' => 'Jalan Meta', 'title' => 'Jalan Meta']);

        SpatialLayerMetadata::create([
            'spatial_layer_id' => $layer->id,
            'source_name' => 'Dinas PUPR',
            'data_reference_year' => 2026,
        ]);

        $this->assertSame('Dinas PUPR', $layer->fresh()->metadata->source_name);
    }
}
