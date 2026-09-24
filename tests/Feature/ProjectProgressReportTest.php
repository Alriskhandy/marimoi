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

class ProjectProgressReportTest extends TestCase
{
    use RefreshDatabase;

    private function roleWithPermissions(string $slug, array $permissions): Role
    {
        $role = Role::create(['slug' => $slug, 'name' => ucfirst(str_replace('-', ' ', $slug)), 'description' => null]);

        foreach ($permissions as $name) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }

        return $role;
    }

    private function proyek(?int $opdId = null): DataSpatial
    {
        $category = Category::create(['type' => 'tematik', 'nama' => 'Jalan Provinsi', 'warna' => '#0d6efd']);
        $uploader = User::factory()->create();

        return DataSpatial::factory()->create([
            'user_id' => $uploader->id,
            'kategori_id' => $category->id,
            'data_type' => 'proyek_strategis',
            'sub_type' => 'psd',
            'opd_pengelola_id' => $opdId,
        ]);
    }

    public function test_admin_bappeda_can_add_progress_report_for_any_project(): void
    {
        $role = $this->roleWithPermissions('admin-bappeda', ['project-progress.view', 'project-progress.create']);
        $admin = User::factory()->create(['role_id' => $role->id]);
        $opd = Opd::create(['name' => 'Dinas PUPR', 'singkatan' => 'PUPR']);
        $proyek = $this->proyek($opd->id);

        $response = $this->actingAs($admin)->post(route('project-progress.store', $proyek->uuid), [
            'tahun_anggaran' => 2026,
            'periode_laporan' => 'Triwulan 1',
            'pagu' => 1000000000,
            'realisasi_anggaran' => 250000000,
            'progres_fisik_persen' => 25,
            'status' => 'on_track',
        ]);

        $response->assertRedirect(route('project-progress.show', $proyek->uuid));
        $this->assertDatabaseHas('project_progress_reports', [
            'data_spatial_id' => $proyek->id,
            'opd_id' => $opd->id,
            'periode_laporan' => 'Triwulan 1',
        ]);
    }

    public function test_admin_opd_can_add_progress_report_for_their_own_project(): void
    {
        $opd = Opd::create(['name' => 'Dinas Kesehatan', 'singkatan' => 'DINKES']);
        $role = $this->roleWithPermissions('admin-opd', ['project-progress.view', 'project-progress.create']);
        $admin = User::factory()->create(['role_id' => $role->id, 'opd_id' => $opd->id]);
        $proyek = $this->proyek($opd->id);

        $response = $this->actingAs($admin)->post(route('project-progress.store', $proyek->uuid), [
            'tahun_anggaran' => 2026,
            'periode_laporan' => 'Triwulan 1',
            'progres_fisik_persen' => 10,
            'status' => 'belum_mulai',
        ]);

        $response->assertRedirect(route('project-progress.show', $proyek->uuid));
        $this->assertDatabaseHas('project_progress_reports', ['data_spatial_id' => $proyek->id]);
    }

    public function test_admin_opd_cannot_add_progress_report_for_other_opd_project(): void
    {
        $ownOpd = Opd::create(['name' => 'Dinas Kesehatan', 'singkatan' => 'DINKES']);
        $otherOpd = Opd::create(['name' => 'Dinas PUPR', 'singkatan' => 'PUPR']);
        $role = $this->roleWithPermissions('admin-opd', ['project-progress.view', 'project-progress.create']);
        $admin = User::factory()->create(['role_id' => $role->id, 'opd_id' => $ownOpd->id]);
        $proyek = $this->proyek($otherOpd->id);

        $response = $this->actingAs($admin)->post(route('project-progress.store', $proyek->uuid), [
            'tahun_anggaran' => 2026,
            'periode_laporan' => 'Triwulan 1',
            'progres_fisik_persen' => 10,
            'status' => 'belum_mulai',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('project_progress_reports', 0);
    }

    public function test_duplicate_period_for_same_project_is_rejected(): void
    {
        $role = $this->roleWithPermissions('admin-bappeda', ['project-progress.view', 'project-progress.create']);
        $admin = User::factory()->create(['role_id' => $role->id]);
        $proyek = $this->proyek();

        ProjectProgressReport::factory()->create([
            'data_spatial_id' => $proyek->id,
            'tahun_anggaran' => 2026,
            'periode_laporan' => 'Triwulan 1',
        ]);

        $response = $this->actingAs($admin)->post(route('project-progress.store', $proyek->uuid), [
            'tahun_anggaran' => 2026,
            'periode_laporan' => 'Triwulan 1',
            'progres_fisik_persen' => 50,
            'status' => 'on_track',
        ]);

        $response->assertSessionHasErrors('periode_laporan');
        $this->assertDatabaseCount('project_progress_reports', 1);
    }

    public function test_progress_percentage_validation_rejects_out_of_range_values(): void
    {
        $role = $this->roleWithPermissions('admin-bappeda', ['project-progress.view', 'project-progress.create']);
        $admin = User::factory()->create(['role_id' => $role->id]);
        $proyek = $this->proyek();

        $response = $this->actingAs($admin)->post(route('project-progress.store', $proyek->uuid), [
            'tahun_anggaran' => 2026,
            'periode_laporan' => 'Triwulan 1',
            'progres_fisik_persen' => 150,
            'status' => 'on_track',
        ]);

        $response->assertSessionHasErrors('progres_fisik_persen');
        $this->assertDatabaseCount('project_progress_reports', 0);
    }

    public function test_guest_cannot_access_progress_report_routes(): void
    {
        $proyek = $this->proyek();

        $this->get(route('project-progress.index'))->assertRedirect(route('login'));
        $this->get(route('project-progress.create', $proyek->uuid))->assertRedirect(route('login'));
        $this->post(route('project-progress.store', $proyek->uuid), [])->assertRedirect(route('login'));
    }

    public function test_admin_bappeda_can_update_existing_report_and_a_revision_is_recorded(): void
    {
        $role = $this->roleWithPermissions('admin-bappeda', ['project-progress.view', 'project-progress.edit']);
        $admin = User::factory()->create(['role_id' => $role->id]);
        $proyek = $this->proyek();

        $laporan = ProjectProgressReport::factory()->create([
            'data_spatial_id' => $proyek->id,
            'tahun_anggaran' => 2026,
            'periode_laporan' => 'Triwulan 1',
            'pagu' => 1000000000,
            'realisasi_anggaran' => 200000000,
            'progres_fisik_persen' => 20,
            'status' => 'on_track',
            'sumber_data' => 'manual',
        ]);

        $response = $this->actingAs($admin)->put(route('project-progress.laporan.update', [$proyek->uuid, $laporan->id]), [
            'pagu' => 1000000000,
            'realisasi_anggaran' => 600000000,
            'progres_fisik_persen' => 60,
            'status' => 'on_track',
            'catatan' => 'Pembayaran termin 2 sudah cair.',
        ]);

        $response->assertRedirect(route('project-progress.show', $proyek->uuid));

        $laporan->refresh();
        $this->assertEquals(60, (float) $laporan->progres_fisik_persen);
        $this->assertEquals(600000000, (float) $laporan->realisasi_anggaran);
        // sumber_data tidak ikut berubah oleh pembaruan manual
        $this->assertSame('manual', $laporan->sumber_data);

        $this->assertDatabaseCount('project_progress_report_revisions', 1);
        $revisi = $laporan->revisions()->first();
        $this->assertSame($admin->id, $revisi->diperbarui_oleh);
        $this->assertEquals(20, (float) $revisi->data_sebelumnya['progres_fisik_persen']);
        $this->assertEquals(200000000, (float) $revisi->data_sebelumnya['realisasi_anggaran']);
    }

    public function test_admin_opd_cannot_update_report_belonging_to_other_opd_project(): void
    {
        $ownOpd = Opd::create(['name' => 'Dinas Kesehatan', 'singkatan' => 'DINKES']);
        $otherOpd = Opd::create(['name' => 'Dinas PUPR', 'singkatan' => 'PUPR']);
        $role = $this->roleWithPermissions('admin-opd', ['project-progress.view', 'project-progress.edit']);
        $admin = User::factory()->create(['role_id' => $role->id, 'opd_id' => $ownOpd->id]);
        $proyek = $this->proyek($otherOpd->id);

        $laporan = ProjectProgressReport::factory()->create([
            'data_spatial_id' => $proyek->id,
            'tahun_anggaran' => 2026,
            'periode_laporan' => 'Triwulan 1',
        ]);

        $response = $this->actingAs($admin)->put(route('project-progress.laporan.update', [$proyek->uuid, $laporan->id]), [
            'progres_fisik_persen' => 90,
            'status' => 'selesai',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('project_progress_report_revisions', 0);
    }

    public function test_update_rejects_out_of_range_percentage_and_keeps_original_value(): void
    {
        $role = $this->roleWithPermissions('admin-bappeda', ['project-progress.view', 'project-progress.edit']);
        $admin = User::factory()->create(['role_id' => $role->id]);
        $proyek = $this->proyek();

        $laporan = ProjectProgressReport::factory()->create([
            'data_spatial_id' => $proyek->id,
            'tahun_anggaran' => 2026,
            'periode_laporan' => 'Triwulan 1',
            'progres_fisik_persen' => 30,
        ]);

        $response = $this->actingAs($admin)->put(route('project-progress.laporan.update', [$proyek->uuid, $laporan->id]), [
            'progres_fisik_persen' => 130,
            'status' => 'on_track',
        ]);

        $response->assertSessionHasErrors('progres_fisik_persen');
        $this->assertEquals(30, (float) $laporan->refresh()->progres_fisik_persen);
        $this->assertDatabaseCount('project_progress_report_revisions', 0);
    }

    public function test_user_without_edit_permission_cannot_update_report(): void
    {
        $role = $this->roleWithPermissions('admin-bappeda', ['project-progress.view', 'project-progress.create']);
        $admin = User::factory()->create(['role_id' => $role->id]);
        $proyek = $this->proyek();

        $laporan = ProjectProgressReport::factory()->create([
            'data_spatial_id' => $proyek->id,
            'tahun_anggaran' => 2026,
            'periode_laporan' => 'Triwulan 1',
        ]);

        $response = $this->actingAs($admin)->get(route('project-progress.laporan.edit', [$proyek->uuid, $laporan->id]));

        $response->assertForbidden();
    }
}
