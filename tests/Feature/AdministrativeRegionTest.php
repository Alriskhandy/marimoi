<?php

namespace Tests\Feature;

use App\Models\AdministrativeRegion;
use App\Models\SpatialLayer;
use App\Models\SpatialLayerFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * `test_spatial_layer_can_cover_multiple_regions` (relasi many-to-many
 * SpatialLayer::regions() lewat pivot `spatial_layer_regions`) DIHAPUS di
 * sini — pivot itu punya FK sungguhan ke tabel v2 `spatial_layers` (bigint,
 * lihat migration create_administrative_regions_table), jadi tidak bisa
 * dipakai dengan PK uuid layers_v3 (plan mellow-weaving-eclipse Fase 3) sama
 * sekali tanpa migration pivot baru. Relasinya sendiri sudah dikonfirmasi
 * tidak dipakai controller/view mana pun (dead code di luar test ini) —
 * redesain pivot-nya ditunda, bukan prioritas Fase 3 (admin SpatialLayer).
 */
class AdministrativeRegionTest extends TestCase
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

    private function createLayer(array $overrides = []): SpatialLayer
    {
        return SpatialLayer::create(array_merge([
            'category_id' => $this->categoryId(),
            'layer_type_id' => 4,
            'code' => 'layer-'.Str::random(8),
            'slug' => 'layer-'.Str::random(8),
            'name' => 'Layer',
        ], $overrides));
    }

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
        $layer = $this->createLayer(['slug' => 'jalan-wilayah', 'name' => 'Jalan']);

        $feature = SpatialLayerFeature::create([
            'layer_id' => $layer->id,
            'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)'),
            'region_id' => $region->id,
        ]);

        $this->assertTrue($feature->fresh()->region->is($region));

        $region->delete();

        $this->assertNull($feature->fresh()->region_id);
    }

    public function test_import_command_reports_not_yet_implemented(): void
    {
        $this->artisan('marimoi:import-wilayah', ['path' => 'dummy.geojson'])
            ->assertExitCode(1);
    }
}
