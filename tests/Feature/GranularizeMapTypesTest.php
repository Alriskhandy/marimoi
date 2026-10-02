<?php

namespace Tests\Feature;

use App\Models\MapType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regresi untuk marimoi:granularize-map-types (docs/marimoi v2/04_implementation/
 * 12-implementasi-perbaikan-pemetaan.md Bagian 1.6 langkah 1). Command ini
 * operasi di atas tabel v2 `spatial_layers` LANGSUNG lewat DB::table()
 * (bukan Eloquent `SpatialLayer`, yang sejak Fase 3 mellow-weaving-eclipse
 * sudah diarahkan ke layers_v3 tanpa kolom parent_id) — fixture di sini
 * ikut pola yang sama.
 */
class GranularizeMapTypesTest extends TestCase
{
    use RefreshDatabase;

    private function layer(array $overrides = []): int
    {
        return DB::table('spatial_layers_legacy_v2')->insertGetId(array_merge([
            'public_id' => (string) Str::uuid(),
            'slug' => 'layer-'.Str::random(8),
            'name' => 'Layer Uji',
            'title' => 'Layer Uji',
            'visibility' => 'private',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function mapTypeIdOf(int $layerId): ?int
    {
        return DB::table('spatial_layers_legacy_v2')->where('id', $layerId)->value('map_type_id');
    }

    public function test_root_layer_gets_its_own_new_map_type(): void
    {
        $rootId = $this->layer(['name' => 'Jalan Provinsi']);

        $this->artisan('marimoi:granularize-map-types')->assertExitCode(0);

        $mapType = MapType::where('nama', 'Jalan Provinsi')->first();
        $this->assertNotNull($mapType);
        $this->assertSame($mapType->id, $this->mapTypeIdOf($rootId));
    }

    public function test_child_layer_follows_root_map_type_not_its_own(): void
    {
        $rootId = $this->layer(['name' => 'Jalan Provinsi']);
        $childId = $this->layer(['name' => 'Jalan Provinsi - Ruas A', 'parent_id' => $rootId]);
        $grandchildId = $this->layer(['name' => 'Jalan Provinsi - Ruas A - Segmen 1', 'parent_id' => $childId]);

        $this->artisan('marimoi:granularize-map-types');

        $mapType = MapType::where('nama', 'Jalan Provinsi')->firstOrFail();
        $this->assertSame($mapType->id, $this->mapTypeIdOf($childId));
        $this->assertSame($mapType->id, $this->mapTypeIdOf($grandchildId));
        $this->assertFalse(MapType::where('nama', 'Jalan Provinsi - Ruas A')->exists());
    }

    public function test_dry_run_does_not_persist_changes(): void
    {
        $rootId = $this->layer(['name' => 'Sekolah']);

        $this->artisan('marimoi:granularize-map-types --dry-run');

        $this->assertNull($this->mapTypeIdOf($rootId));
        $this->assertFalse(MapType::where('nama', 'Sekolah')->exists());
    }

    public function test_running_twice_does_not_duplicate_map_type(): void
    {
        $this->layer(['name' => 'Sungai']);

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
        $this->layer(['name' => 'Jembatan', 'map_type_id' => $old->id]);

        $this->artisan('marimoi:granularize-map-types');

        $this->assertFalse($old->fresh()->is_active);
    }
}
