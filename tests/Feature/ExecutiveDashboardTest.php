<?php

namespace Tests\Feature;

use App\Models\AdministrativeRegion;
use App\Models\Category;
use App\Models\DataSpatial;
use App\Models\DevelopmentProject;
use App\Models\Permission;
use App\Models\ProjectProgressReport;
use App\Models\Role;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExecutiveDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function roleWith(string $slug, array $permissions = []): Role
    {
        $role = Role::create(['name' => ucfirst($slug), 'slug' => $slug, 'description' => null]);

        foreach ($permissions as $name) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }

        return $role;
    }

    private function userFor(Role $role): User
    {
        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_user_without_permission_cannot_access_dashboard(): void
    {
        $user = $this->userFor($this->roleWith('admin-bappeda'));

        $this->actingAs($user)->getJson(route('dashboard.api.eksekutif.summary'))->assertForbidden();
    }

    public function test_summary_counts_projects_and_review_flag_correctly(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['dashboard.view']));

        DevelopmentProject::create(['project_code' => 'A', 'name' => 'Proyek A', 'fiscal_year' => 2026, 'status' => 'berjalan', 'budget_amount' => 1000000, 'needs_review' => true]);
        DevelopmentProject::create(['project_code' => 'B', 'name' => 'Proyek B', 'fiscal_year' => 2026, 'status' => 'selesai', 'budget_amount' => 2000000, 'needs_review' => false]);
        DevelopmentProject::create(['project_code' => 'C', 'name' => 'Proyek C', 'fiscal_year' => 2025, 'status' => 'berjalan', 'budget_amount' => 500000, 'needs_review' => false]);

        $response = $this->actingAs($admin)->getJson(route('dashboard.api.eksekutif.summary', ['fiscal_year' => 2026]));

        $response->assertOk();
        $response->assertJson([
            'jumlah_proyek' => 2,
            'proyek_berjalan' => 1,
            'proyek_selesai' => 1,
            'perlu_review' => 1,
            'total_anggaran' => 3000000.0,
        ]);
    }

    public function test_summary_includes_realization_from_linked_progress_reports(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['dashboard.view']));
        $project = DevelopmentProject::create(['project_code' => 'D', 'name' => 'Proyek D', 'fiscal_year' => 2026, 'budget_amount' => 1000000]);
        $category = Category::create(['type' => 'tematik', 'nama' => 'Kategori Uji', 'warna' => '#000']);
        $dataSpatial = DataSpatial::factory()->create(['kategori_id' => $category->id]);

        ProjectProgressReport::create([
            'development_project_id' => $project->id,
            'data_spatial_id' => $dataSpatial->id,
            'tahun_anggaran' => 2026,
            'periode_laporan' => 'Q1',
            'realisasi_anggaran' => 250000,
            'progres_fisik_persen' => 25,
            'dilaporkan_oleh' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->getJson(route('dashboard.api.eksekutif.summary'));

        $response->assertOk();
        $response->assertJsonFragment(['total_realisasi' => 250000.0, 'rata_progres_fisik' => 25.0]);
    }

    public function test_by_sector_groups_budget_by_sector_name(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['dashboard.view']));
        $pupr = Sector::where('code', 'pupr')->firstOrFail();

        DevelopmentProject::create(['project_code' => 'E', 'name' => 'E', 'fiscal_year' => 2026, 'sector_id' => $pupr->id, 'budget_amount' => 1000000]);
        DevelopmentProject::create(['project_code' => 'F', 'name' => 'F', 'fiscal_year' => 2026, 'budget_amount' => 500000]);

        $response = $this->actingAs($admin)->getJson(route('dashboard.api.eksekutif.sektor'));

        $response->assertOk();
        $response->assertJsonFragment(['jumlah_proyek' => 1, 'total_anggaran' => 1000000.0]);
        $data = $response->json();
        $this->assertArrayHasKey('PUPR', $data);
        $this->assertArrayHasKey('Tanpa Sektor', $data);
    }

    public function test_by_region_counts_projects_per_region_via_pivot(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['dashboard.view']));
        $region = AdministrativeRegion::create(['code_kemendagri' => '82.01', 'name' => 'Halmahera Barat', 'level' => 'kabupaten_kota']);
        $project = DevelopmentProject::create(['project_code' => 'G', 'name' => 'G', 'fiscal_year' => 2026]);
        $project->regions()->attach($region->id);

        $response = $this->actingAs($admin)->getJson(route('dashboard.api.eksekutif.wilayah'));

        $response->assertOk();
        $response->assertJson(['Halmahera Barat' => 1]);
    }

    public function test_trend_groups_by_fiscal_year(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['dashboard.view']));
        DevelopmentProject::create(['project_code' => 'H', 'name' => 'H', 'fiscal_year' => 2025, 'budget_amount' => 100]);
        DevelopmentProject::create(['project_code' => 'I', 'name' => 'I', 'fiscal_year' => 2026, 'budget_amount' => 200]);
        DevelopmentProject::create(['project_code' => 'J', 'name' => 'J', 'fiscal_year' => 2026, 'budget_amount' => 300]);

        $response = $this->actingAs($admin)->getJson(route('dashboard.api.eksekutif.tren'));

        $response->assertOk();
        $response->assertJson([
            '2025' => ['jumlah_proyek' => 1, 'total_anggaran' => 100.0],
            '2026' => ['jumlah_proyek' => 2, 'total_anggaran' => 500.0],
        ]);
    }

    public function test_filters_can_be_combined(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['dashboard.view']));
        $pupr = Sector::where('code', 'pupr')->firstOrFail();
        $kesehatan = Sector::where('code', 'kesehatan')->firstOrFail();

        DevelopmentProject::create(['project_code' => 'K', 'name' => 'K', 'fiscal_year' => 2026, 'sector_id' => $pupr->id]);
        DevelopmentProject::create(['project_code' => 'L', 'name' => 'L', 'fiscal_year' => 2026, 'sector_id' => $kesehatan->id]);

        $response = $this->actingAs($admin)->getJson(route('dashboard.api.eksekutif.summary', ['sector_id' => $pupr->id]));

        $response->assertOk();
        $response->assertJson(['jumlah_proyek' => 1]);
    }
}
