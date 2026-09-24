<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DataSpatial;
use App\Models\Opd;
use App\Models\Permission;
use App\Models\ProjectProgressReport;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PembangunanDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function grantProjectProgressView(Role $role): void
    {
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'project-progress.view', 'guard_name' => 'web']));
    }

    private function proyek(?int $opdId, ?int $kategoriId = null): DataSpatial
    {
        $category = $kategoriId
            ? Category::find($kategoriId)
            : Category::create(['type' => 'tematik', 'nama' => 'Jalan Provinsi', 'warna' => '#0d6efd']);
        $uploader = User::factory()->create();

        return DataSpatial::factory()->create([
            'user_id' => $uploader->id,
            'kategori_id' => $category->id,
            'data_type' => 'proyek_strategis',
            'sub_type' => 'psd',
            'opd_pengelola_id' => $opdId,
        ]);
    }

    public function test_dashboard_shows_aggregated_cards_for_selected_year(): void
    {
        $role = Role::create(['slug' => 'admin-bappeda', 'name' => 'Admin Bappeda', 'description' => null]);
        $this->grantProjectProgressView($role);
        $admin = User::factory()->create(['role_id' => $role->id]);

        $opd = Opd::create(['name' => 'Dinas PUPR', 'singkatan' => 'PUPR']);
        $proyekA = $this->proyek($opd->id);
        $proyekB = $this->proyek($opd->id);

        ProjectProgressReport::factory()->create([
            'data_spatial_id' => $proyekA->id,
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2026,
            'periode_laporan' => 'Triwulan 1',
            'pagu' => 1000000000,
            'realisasi_anggaran' => 500000000,
            'progres_fisik_persen' => 50,
            'status' => 'on_track',
        ]);
        ProjectProgressReport::factory()->create([
            'data_spatial_id' => $proyekB->id,
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2026,
            'periode_laporan' => 'Triwulan 1',
            'pagu' => 1000000000,
            'realisasi_anggaran' => 1000000000,
            'progres_fisik_persen' => 100,
            'status' => 'selesai',
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard.pembangunan', ['tahun' => 2026]));

        $response->assertOk();
        $response->assertViewHas('cards', function (array $cards) {
            return $cards['jumlah_dilaporkan'] === 2
                && (float) $cards['total_pagu'] === 2000000000.0
                && (float) $cards['total_realisasi'] === 1500000000.0
                && $cards['rata_progres_fisik'] === 75.0;
        });
    }

    public function test_admin_opd_only_sees_their_own_opd_data_even_if_query_param_is_tampered(): void
    {
        $ownOpd = Opd::create(['name' => 'Dinas Kesehatan', 'singkatan' => 'DINKES']);
        $otherOpd = Opd::create(['name' => 'Dinas PUPR', 'singkatan' => 'PUPR']);

        $role = Role::create(['slug' => 'admin-opd', 'name' => 'Admin OPD', 'description' => null]);
        $this->grantProjectProgressView($role);
        $admin = User::factory()->create(['role_id' => $role->id, 'opd_id' => $ownOpd->id]);

        $proyekMilikSendiri = $this->proyek($ownOpd->id);
        $proyekOpdLain = $this->proyek($otherOpd->id);

        ProjectProgressReport::factory()->create([
            'data_spatial_id' => $proyekMilikSendiri->id,
            'opd_id' => $ownOpd->id,
            'tahun_anggaran' => 2026,
            'periode_laporan' => 'Triwulan 1',
        ]);
        ProjectProgressReport::factory()->create([
            'data_spatial_id' => $proyekOpdLain->id,
            'opd_id' => $otherOpd->id,
            'tahun_anggaran' => 2026,
            'periode_laporan' => 'Triwulan 1',
        ]);

        // opd_id di query string sengaja diarahkan ke OPD lain — harus tetap diabaikan.
        $response = $this->actingAs($admin)->get(route('dashboard.pembangunan', ['tahun' => 2026, 'opd_id' => $otherOpd->id]));

        $response->assertOk();
        $response->assertViewHas('cards', fn (array $cards) => $cards['jumlah_dilaporkan'] === 1);
    }

    public function test_dashboard_filters_by_status(): void
    {
        $role = Role::create(['slug' => 'admin-bappeda', 'name' => 'Admin Bappeda', 'description' => null]);
        $this->grantProjectProgressView($role);
        $admin = User::factory()->create(['role_id' => $role->id]);

        $opd = Opd::create(['name' => 'Dinas PUPR', 'singkatan' => 'PUPR']);
        $proyekTerlambat = $this->proyek($opd->id);
        $proyekOnTrack = $this->proyek($opd->id);

        ProjectProgressReport::factory()->create([
            'data_spatial_id' => $proyekTerlambat->id,
            'tahun_anggaran' => 2026,
            'periode_laporan' => 'Triwulan 1',
            'status' => 'terlambat',
        ]);
        ProjectProgressReport::factory()->create([
            'data_spatial_id' => $proyekOnTrack->id,
            'tahun_anggaran' => 2026,
            'periode_laporan' => 'Triwulan 1',
            'status' => 'on_track',
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard.pembangunan', ['tahun' => 2026, 'status' => 'terlambat']));

        $response->assertOk();
        $response->assertViewHas('laporan', fn ($laporan) => $laporan->count() === 1 && $laporan->first()->status === 'terlambat');
    }
}
