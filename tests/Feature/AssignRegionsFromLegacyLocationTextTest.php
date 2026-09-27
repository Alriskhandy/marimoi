<?php

namespace Tests\Feature;

use App\Models\AdministrativeRegion;
use App\Models\Category;
use App\Models\DataSpatial;
use App\Models\DevelopmentProject;
use App\Models\SpatialLayer;
use App\Models\SpatialLayerFeature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AssignRegionsFromLegacyLocationTextTest extends TestCase
{
    use RefreshDatabase;

    private function seedRegions(): array
    {
        $halbar = AdministrativeRegion::create(['code_kemendagri' => '82.01', 'name' => 'Kabupaten Halmahera Barat', 'level' => 'kabupaten_kota']);
        $jailolo = AdministrativeRegion::create(['code_kemendagri' => '82.01.01', 'name' => 'Jailolo', 'level' => 'kecamatan', 'parent_id' => $halbar->id]);
        $haltim = AdministrativeRegion::create(['code_kemendagri' => '82.06', 'name' => 'Kabupaten Halmahera Timur', 'level' => 'kabupaten_kota']);

        return [$halbar, $jailolo, $haltim];
    }

    private function makeDataSpatial(string $lokasi, string $dataType = 'tematik'): DataSpatial
    {
        $category = Category::create(['type' => 'tematik', 'nama' => 'Uji', 'warna' => '#000']);
        $user = User::factory()->create();

        return DataSpatial::factory()->create([
            'user_id' => $user->id,
            'kategori_id' => $category->id,
            'data_type' => $dataType,
            'dbf_attributes' => ['LOKASI' => $lokasi],
        ]);
    }

    private function makeFeatureFor(DataSpatial $ds): SpatialLayerFeature
    {
        $layer = SpatialLayer::create(['slug' => 'layer-'.$ds->id, 'name' => 'Layer', 'title' => 'Layer', 'layer_class' => 'thematic']);

        return SpatialLayerFeature::create([
            'spatial_layer_id' => $layer->id,
            'geometry' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)'),
            'legacy_data_spatial_id' => $ds->id,
        ]);
    }

    public function test_assigns_kecamatan_when_pattern_and_name_match_uniquely(): void
    {
        [$halbar, $jailolo] = $this->seedRegions();
        $ds = $this->makeDataSpatial('Jl. Contoh, Desa Uji, Kec. Jailolo, Kab. Halmahera Barat');
        $feature = $this->makeFeatureFor($ds);

        $this->artisan('marimoi:assign-regions-from-location-text')->assertExitCode(0);

        $this->assertSame($jailolo->id, $feature->fresh()->region_id);
    }

    public function test_falls_back_to_kabupaten_when_kecamatan_name_does_not_match(): void
    {
        $this->seedRegions();
        $halbar = AdministrativeRegion::where('code_kemendagri', '82.01')->firstOrFail();
        $ds = $this->makeDataSpatial('Desa Uji, Kec. Nama Yang Tidak Ada, Kab. Halmahera Barat');
        $feature = $this->makeFeatureFor($ds);

        $this->artisan('marimoi:assign-regions-from-location-text');

        $this->assertSame($halbar->id, $feature->fresh()->region_id);
    }

    public function test_does_not_assign_when_no_kec_pattern_present(): void
    {
        $this->seedRegions();
        $ds = $this->makeDataSpatial('Bobong');
        $feature = $this->makeFeatureFor($ds);

        $this->artisan('marimoi:assign-regions-from-location-text');

        $this->assertNull($feature->fresh()->region_id);
    }

    public function test_does_not_assign_when_kabupaten_is_ambiguous(): void
    {
        AdministrativeRegion::create(['code_kemendagri' => '82.01', 'name' => 'Kabupaten Halmahera Barat', 'level' => 'kabupaten_kota']);
        AdministrativeRegion::create(['code_kemendagri' => '82.03', 'name' => 'Kabupaten Halmahera Utara', 'level' => 'kabupaten_kota']);
        // "HALMAHERA" saja cocok dengan kedua kabupaten sekaligus — harus dianggap ambigu.
        $ds = $this->makeDataSpatial('Desa Uji, Kec. Entah, Kab. Halmahera');
        $feature = $this->makeFeatureFor($ds);

        $this->artisan('marimoi:assign-regions-from-location-text');

        $this->assertNull($feature->fresh()->region_id);
    }

    public function test_dry_run_does_not_persist_changes(): void
    {
        [$halbar, $jailolo] = $this->seedRegions();
        $ds = $this->makeDataSpatial('Jl. Contoh, Kec. Jailolo, Kab. Halmahera Barat');
        $feature = $this->makeFeatureFor($ds);

        $this->artisan('marimoi:assign-regions-from-location-text --dry-run');

        $this->assertNull($feature->fresh()->region_id);
    }

    public function test_also_links_development_project_to_matched_region_via_pivot(): void
    {
        [$halbar, $jailolo] = $this->seedRegions();
        $ds = $this->makeDataSpatial('Jl. Contoh, Kec. Jailolo, Kab. Halmahera Barat', 'proyek_strategis');
        $this->makeFeatureFor($ds);
        $project = DevelopmentProject::create([
            'project_code' => 'UJI-PROJ', 'name' => 'Proyek Uji', 'fiscal_year' => 2026,
            'legacy_data_spatial_id' => $ds->id,
        ]);

        $this->artisan('marimoi:assign-regions-from-location-text');

        $this->assertTrue($project->regions()->where('administrative_regions.id', $jailolo->id)->exists());
    }
}
