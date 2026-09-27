<?php

namespace Tests\Feature;

use App\Models\AdministrativeRegion;
use App\Models\Category;
use App\Models\DataSpatial;
use App\Models\DevelopmentProject;
use App\Models\Opd;
use App\Models\ProjectFeedback;
use App\Models\ProjectProgressReport;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DevelopmentProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_gets_public_id_automatically(): void
    {
        $project = DevelopmentProject::create(['project_code' => 'UJI-001', 'name' => 'Proyek Uji', 'fiscal_year' => 2026]);

        $this->assertNotNull($project->public_id);
    }

    public function test_project_belongs_to_owner_opd_and_sector(): void
    {
        $opd = Opd::create(['name' => 'Dinas Uji', 'singkatan' => 'DU']);
        $sector = Sector::where('code', 'pupr')->firstOrFail();

        $project = DevelopmentProject::create([
            'project_code' => 'UJI-002', 'name' => 'Proyek Uji', 'fiscal_year' => 2026,
            'owner_opd_id' => $opd->id, 'sector_id' => $sector->id,
        ]);

        $this->assertTrue($project->owner->is($opd));
        $this->assertTrue($project->sector->is($sector));
    }

    public function test_needs_review_scope_only_returns_flagged_projects(): void
    {
        DevelopmentProject::create(['project_code' => 'UJI-003', 'name' => 'Butuh Review', 'fiscal_year' => 2026, 'needs_review' => true]);
        DevelopmentProject::create(['project_code' => 'UJI-004', 'name' => 'Aman', 'fiscal_year' => 2026, 'needs_review' => false]);

        $result = DevelopmentProject::needsReview()->pluck('name');

        $this->assertTrue($result->contains('Butuh Review'));
        $this->assertFalse($result->contains('Aman'));
    }

    public function test_project_has_many_locations_and_regions(): void
    {
        $project = DevelopmentProject::create(['project_code' => 'UJI-005', 'name' => 'Proyek Lokasi', 'fiscal_year' => 2026]);
        $region = AdministrativeRegion::create(['code_kemendagri' => '82.01', 'name' => 'Halmahera Barat', 'level' => 'kabupaten_kota']);

        $project->locations()->create([
            'geometry_type' => 'ST_Point',
            'geometry' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)'),
        ]);
        $project->regions()->attach($region->id);

        $this->assertCount(1, $project->fresh()->locations);
        $this->assertTrue($project->fresh()->regions->first()->is($region));
    }

    public function test_project_feedback_can_link_to_development_project(): void
    {
        $project = DevelopmentProject::create(['project_code' => 'UJI-006', 'name' => 'Proyek Feedback', 'fiscal_year' => 2026]);

        $feedback = ProjectFeedback::create([
            'development_project_id' => $project->id,
            'nama_pemberi_aspirasi' => 'Warga Uji',
            'nama_proyek' => 'Proyek Feedback (snapshot lama)',
            'kabupaten_kota' => 'Ternate',
            'tanggapan' => 'Semoga cepat selesai',
            'jenis_tanggapan' => 'saran',
            'status' => 'pending',
        ]);

        $this->assertTrue($feedback->developmentProject->is($project));
    }

    public function test_project_progress_report_can_link_to_development_project(): void
    {
        $user = User::factory()->create();
        $opd = Opd::create(['name' => 'Dinas Uji', 'singkatan' => 'DU2']);
        $category = Category::create(['type' => 'tematik', 'nama' => 'Kategori Uji', 'warna' => '#000']);
        $dataSpatial = DataSpatial::factory()->create(['user_id' => $user->id, 'kategori_id' => $category->id]);
        $project = DevelopmentProject::create(['project_code' => 'UJI-007', 'name' => 'Proyek Progres', 'fiscal_year' => 2026]);

        $report = ProjectProgressReport::create([
            'development_project_id' => $project->id,
            'data_spatial_id' => $dataSpatial->id,
            'opd_id' => $opd->id,
            'kategori_id' => $category->id,
            'tahun_anggaran' => 2026,
            'periode_laporan' => 'Q1',
            'dilaporkan_oleh' => $user->id,
        ]);

        $this->assertTrue($report->developmentProject->is($project));
    }
}
