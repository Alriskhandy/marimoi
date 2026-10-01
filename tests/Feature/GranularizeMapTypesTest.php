<?php

namespace Tests\Feature;

use App\Models\MapType;
use App\Models\SpatialLayer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresi untuk marimoi:granularize-map-types (docs/marimoi v2/04_implementation/
 * 12-implementasi-perbaikan-pemetaan.md Bagian 1.6 langkah 1).
 */
class GranularizeMapTypesTest extends TestCase
{
    use RefreshDatabase;

    private function layer(array $overrides = []): SpatialLayer
    {
        return SpatialLayer::create(array_merge([
            'slug' => 'layer-'.uniqid(),
            'name' => 'Layer Uji',
            'title' => 'Layer Uji',
        ], $overrides));
    }

    public function test_root_layer_gets_its_own_new_map_type(): void
    {
        $root = $this->layer(['name' => 'Jalan Provinsi']);

        $this->artisan('marimoi:granularize-map-types')->assertExitCode(0);

        $mapType = MapType::where('nama', 'Jalan Provinsi')->first();
        $this->assertNotNull($mapType);
        $this->assertSame($mapType->id, $root->fresh()->map_type_id);
    }

    public function test_child_layer_follows_root_map_type_not_its_own(): void
    {
        $root = $this->layer(['name' => 'Jalan Provinsi']);
        $child = $this->layer(['name' => 'Jalan Provinsi - Ruas A', 'parent_id' => $root->id]);
        $grandchild = $this->layer(['name' => 'Jalan Provinsi - Ruas A - Segmen 1', 'parent_id' => $child->id]);

        $this->artisan('marimoi:granularize-map-types');

        $mapType = MapType::where('nama', 'Jalan Provinsi')->firstOrFail();
        $this->assertSame($mapType->id, $child->fresh()->map_type_id);
        $this->assertSame($mapType->id, $grandchild->fresh()->map_type_id);
        $this->assertFalse(MapType::where('nama', 'Jalan Provinsi - Ruas A')->exists());
    }

    public function test_dry_run_does_not_persist_changes(): void
    {
        $root = $this->layer(['name' => 'Sekolah']);

        $this->artisan('marimoi:granularize-map-types --dry-run');

        $this->assertNull($root->fresh()->map_type_id);
        $this->assertFalse(MapType::where('nama', 'Sekolah')->exists());
    }

    public function test_running_twice_does_not_duplicate_map_type(): void
    {
        $root = $this->layer(['name' => 'Sungai']);

        $this->artisan('marimoi:granularize-map-types');
        $this->artisan('marimoi:granularize-map-types');

        $this->assertSame(1, MapType::where('nama', 'Sungai')->count());
    }

    public function test_old_coarse_map_type_deactivated_when_no_longer_referenced(): void
    {
        // Migration create_map_types_table sudah menyeed 'tematik' dkk. secara
        // default — ambil baris yang sudah ada, jangan create ulang (unique slug).
        $old = MapType::where('slug', 'tematik')->firstOrFail();
        $old->update(['is_active' => true]);
        $root = $this->layer(['name' => 'Jembatan', 'map_type_id' => $old->id]);

        $this->artisan('marimoi:granularize-map-types');

        $this->assertFalse($old->fresh()->is_active);
    }
}
