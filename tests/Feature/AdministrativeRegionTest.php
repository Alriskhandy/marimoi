<?php

namespace Tests\Feature;

use App\Models\AdministrativeRegion;
use App\Models\SpatialLayer;
use App\Models\SpatialLayerFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdministrativeRegionTest extends TestCase
{
    use RefreshDatabase;

    public function test_hierarchy_provinsi_kabupaten_kecamatan(): void
    {
        $provinsi = AdministrativeRegion::create(['code_kemendagri' => '82', 'name' => 'Maluku Utara', 'level' => 'provinsi']);
        $kabupaten = AdministrativeRegion::create(['code_kemendagri' => '82.01', 'name' => 'Halmahera Barat', 'level' => 'kabupaten_kota', 'parent_id' => $provinsi->id]);
        $kecamatan = AdministrativeRegion::create(['code_kemendagri' => '82.01.01', 'name' => 'Jailolo', 'level' => 'kecamatan', 'parent_id' => $kabupaten->id]);

        $this->assertTrue($kabupaten->parent->is($provinsi));
        $this->assertTrue($kecamatan->parent->is($kabupaten));
        $this->assertTrue($provinsi->children->pluck('id')->contains($kabupaten->id));
    }

    public function test_scope_level_filters_by_level(): void
    {
        AdministrativeRegion::create(['code_kemendagri' => '82', 'name' => 'Maluku Utara', 'level' => 'provinsi']);
        AdministrativeRegion::create(['code_kemendagri' => '82.01', 'name' => 'Halmahera Barat', 'level' => 'kabupaten_kota']);
        AdministrativeRegion::create(['code_kemendagri' => '82.02', 'name' => 'Halmahera Tengah', 'level' => 'kabupaten_kota']);

        $this->assertCount(1, AdministrativeRegion::level('provinsi')->get());
        $this->assertCount(2, AdministrativeRegion::level('kabupaten_kota')->get());
    }

    public function test_feature_region_id_is_set_null_when_region_deleted(): void
    {
        $region = AdministrativeRegion::create(['code_kemendagri' => '82.01', 'name' => 'Halmahera Barat', 'level' => 'kabupaten_kota']);
        $layer = SpatialLayer::create(['slug' => 'jalan-wilayah', 'name' => 'Jalan', 'title' => 'Jalan', 'layer_class' => 'thematic']);

        $feature = SpatialLayerFeature::create([
            'spatial_layer_id' => $layer->id,
            'geometry' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)'),
            'region_id' => $region->id,
        ]);

        $this->assertTrue($feature->fresh()->region->is($region));

        $region->delete();

        $this->assertNull($feature->fresh()->region_id);
    }

    public function test_spatial_layer_can_cover_multiple_regions(): void
    {
        $layer = SpatialLayer::create(['slug' => 'jalan-lintas', 'name' => 'Jalan Lintas', 'title' => 'Jalan Lintas', 'layer_class' => 'thematic']);
        $regionA = AdministrativeRegion::create(['code_kemendagri' => '82.01', 'name' => 'Halmahera Barat', 'level' => 'kabupaten_kota']);
        $regionB = AdministrativeRegion::create(['code_kemendagri' => '82.02', 'name' => 'Halmahera Tengah', 'level' => 'kabupaten_kota']);

        $layer->regions()->attach([$regionA->id, $regionB->id]);

        $this->assertCount(2, $layer->fresh()->regions);
        $this->assertCount(1, $regionA->fresh()->spatialLayers);
    }

    public function test_import_command_reports_not_yet_implemented(): void
    {
        $this->artisan('marimoi:import-wilayah', ['path' => 'dummy.geojson'])
            ->assertExitCode(1);
    }
}
