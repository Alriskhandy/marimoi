<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\DashboardMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Dashboard menampilkan isi berbeda per role: eksekutif (pimpinan), OPD (admin-opd),
 * dan operasional (super-admin / admin-bappeda). Lihat App\Support\DashboardMetrics.
 */
class DashboardRoleProfileTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $slug, array $attributes = []): User
    {
        $role = Role::firstOrCreate(['slug' => $slug], ['name' => Str::headline($slug), 'description' => null]);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'dashboard.view', 'guard_name' => 'web']));

        return User::factory()->create(['role_id' => $role->id] + $attributes);
    }

    private function opd(string $name): int
    {
        return DB::table('opd')->insertGetId(['name' => $name, 'singkatan' => Str::upper(Str::random(6)), 'created_at' => now(), 'updated_at' => now()]);
    }

    private function aspirasi(int $opdId, string $status, ?string $createdAt = null): void
    {
        $kategoriId = DB::table('kategori_aspirasi')->where('opd_id', $opdId)->value('id')
            ?? DB::table('kategori_aspirasi')->insertGetId(['opd_id' => $opdId, 'nama_kategori' => 'Kategori '.$opdId, 'created_at' => now(), 'updated_at' => now()]);

        DB::table('aspirasi')->insert([
            'kategori_aspirasi_id' => $kategoriId,
            'nomor_tiket' => 'ASP-'.Str::upper(Str::random(8)),
            'nama_pengirim' => 'Warga',
            'email' => 'warga@example.test',
            'alamat' => 'Sofifi',
            'judul_aspirasi' => 'Jalan rusak',
            'isi_aspirasi' => 'Mohon diperbaiki',
            'status' => $status,
            'created_at' => $createdAt ?? now(),
            'updated_at' => now(),
        ]);
    }

    private function project(?int $opdId, float $budget, int $year = 2026, string $status = 'berjalan'): void
    {
        DB::table('development_projects')->insert([
            'public_id' => (string) Str::uuid(),
            'project_code' => 'PRJ-'.Str::upper(Str::random(8)),
            'name' => 'Proyek',
            'owner_opd_id' => $opdId,
            'fiscal_year' => $year,
            'status' => $status,
            'budget_amount' => $budget,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_pimpinan_sees_executive_dashboard(): void
    {
        $opdId = $this->opd('Dinas PUPR');
        $this->project($opdId, 2_000_000_000_000, 2025);
        $this->project($opdId, 1_000_000_000_000, 2026, 'selesai');
        $this->aspirasi($opdId, 'selesai');
        $this->aspirasi($opdId, 'pending');

        $response = $this->actingAs($this->userWithRole('pimpinan'))->get(route('dashboard'));

        $response->assertOk()
            ->assertViewHas('profile', DashboardMetrics::PROFILE_EXECUTIVE)
            ->assertViewHas('projects', fn (array $projects) => $projects['count'] === 2 && $projects['budget'] === 3e12 && $projects['completed'] === 1)
            ->assertViewHas('aspirasi', fn (array $aspirasi) => $aspirasi['completionRate'] === 50.0)
            ->assertViewHas('opdScorecard', fn ($rows) => $rows->firstWhere('name', 'Dinas PUPR')->projects === 2)
            ->assertViewHas('highlights')
            ->assertViewMissing('recentAspirasi')
            ->assertSee('Dashboard Eksekutif')
            ->assertSee('Rapor Kinerja OPD')
            ->assertSee('Rp 3,00 T')
            ->assertDontSee('Aspirasi Terbaru');
    }

    public function test_admin_sees_operational_dashboard_with_attention_items(): void
    {
        $opdId = $this->opd('Bappeda');
        $this->aspirasi($opdId, 'pending', now()->subDays(10)->toDateTimeString());
        $this->aspirasi($opdId, 'pending');

        $response = $this->actingAs($this->userWithRole('super-admin'))->get(route('dashboard'));

        $response->assertOk()
            ->assertViewHas('profile', DashboardMetrics::PROFILE_ADMIN)
            ->assertViewHas('aspirasi', fn (array $aspirasi) => $aspirasi['pending'] === 2 && $aspirasi['overdue'] === 1)
            ->assertViewHas('attention', fn (array $items) => collect($items)->pluck('label')->contains('Aspirasi terlambat ditanggapi'))
            ->assertViewHas('reach')
            ->assertSee('Dashboard Operasional')
            ->assertSee('Aspirasi Terbaru')
            ->assertSee('Kunjungan Publik')
            ->assertDontSee('Rapor Kinerja OPD');
    }

    public function test_admin_opd_only_sees_data_of_their_opd(): void
    {
        $ownOpd = $this->opd('Dinas Kesehatan');
        $otherOpd = $this->opd('Dinas Pendidikan');
        $this->aspirasi($ownOpd, 'pending');
        $this->aspirasi($otherOpd, 'pending');
        $this->aspirasi($otherOpd, 'selesai');
        $this->project($ownOpd, 5_000_000_000);
        $this->project($otherOpd, 9_000_000_000);

        $response = $this->actingAs($this->userWithRole('admin-opd', ['opd_id' => $ownOpd]))->get(route('dashboard'));

        $response->assertOk()
            ->assertViewHas('profile', DashboardMetrics::PROFILE_OPD)
            ->assertViewHas('aspirasi', fn (array $aspirasi) => $aspirasi['total'] === 1 && $aspirasi['pending'] === 1)
            ->assertViewHas('projects', fn (array $projects) => $projects['count'] === 1 && $projects['budget'] === 5e9)
            ->assertViewMissing('reach')
            ->assertSee('Dashboard OPD')
            ->assertDontSee('Kunjungan Publik');
    }

    public function test_super_admin_and_admin_bappeda_can_switch_to_executive_view(): void
    {
        foreach (['super-admin', 'admin-bappeda'] as $slug) {
            $user = $this->userWithRole($slug);

            $this->actingAs($user)->get(route('dashboard'))
                ->assertOk()
                ->assertViewHas('profile', DashboardMetrics::PROFILE_ADMIN)
                ->assertViewHas('canSwitchToExecutive', true)
                ->assertSee(route('dashboard', ['tampilan' => DashboardMetrics::PROFILE_EXECUTIVE]), false);

            $this->actingAs($user)->get(route('dashboard', ['tampilan' => DashboardMetrics::PROFILE_EXECUTIVE]))
                ->assertOk()
                ->assertViewHas('profile', DashboardMetrics::PROFILE_EXECUTIVE)
                ->assertSee('Dashboard Eksekutif')
                ->assertSee('Rapor Kinerja OPD')
                ->assertSee('Operasional');
        }
    }

    public function test_admin_opd_and_pimpinan_cannot_switch_view(): void
    {
        $opdId = $this->opd('Dinas Sosial');

        $this->actingAs($this->userWithRole('admin-opd', ['opd_id' => $opdId]))
            ->get(route('dashboard', ['tampilan' => DashboardMetrics::PROFILE_EXECUTIVE]))
            ->assertOk()
            ->assertViewHas('profile', DashboardMetrics::PROFILE_OPD)
            ->assertViewHas('canSwitchToExecutive', false)
            ->assertDontSee('Rapor Kinerja OPD');

        $this->actingAs($this->userWithRole('pimpinan'))
            ->get(route('dashboard', ['tampilan' => DashboardMetrics::PROFILE_ADMIN]))
            ->assertOk()
            ->assertViewHas('profile', DashboardMetrics::PROFILE_EXECUTIVE)
            ->assertViewHas('canSwitchToExecutive', false);
    }

    public function test_dashboard_renders_with_empty_data(): void
    {
        $this->actingAs($this->userWithRole('pimpinan'))->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Belum ada data proyek pembangunan.');

        $this->actingAs($this->userWithRole('admin-bappeda'))->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Semua beres');
    }

    public function test_pimpinan_role_is_granted_dashboard_access_by_migration(): void
    {
        $role = Role::where('slug', 'pimpinan')->first();

        $this->assertNotNull($role);
        $this->assertTrue($role->hasPermissionTo('dashboard.view'));
    }

    public function test_rupiah_formatter_uses_compact_units(): void
    {
        $this->assertSame('Rp 6,22 T', DashboardMetrics::rupiah(6_221_457_405_976));
        $this->assertSame('Rp 109,9 M', DashboardMetrics::rupiah(109_927_405_976));
        $this->assertSame('Rp 450,0 Jt', DashboardMetrics::rupiah(450_000_000));
        $this->assertSame('Rp 12.500', DashboardMetrics::rupiah(12_500));
    }
}
