<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Cabut permission `project-progress.view` — halaman "Dashboard
     * Pembangunan" (route dashboard.pembangunan, PembangunanDashboardController,
     * view dashboard-pembangunan) dihapus 2026-10-10 dan permission ini adalah
     * satu-satunya penjaganya (route middleware + submenu sidebar).
     *
     * TIDAK menyentuh tabel `project_progress_reports` maupun model
     * ProjectProgressReport: keduanya masih dipakai ExecutiveDashboardController
     * (endpoint dashboard/api/eksekutif/*) dan DevelopmentProject.
     *
     * PermissionSeeder::RETIRED_PREFIXES juga memuat prefix ini sebagai jaring
     * kedua kalau seeder dijalankan di lingkungan yang belum kena migration.
     */
    public function up(): void
    {
        $this->forgetPermission('project-progress.view');
    }

    public function down(): void
    {
        $existing = DB::table('permissions')->where('name', 'project-progress.view')->exists();

        if ($existing) {
            return;
        }

        $permissionId = DB::table('permissions')->insertGetId([
            'name' => 'project-progress.view',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Dulu bawaan untuk admin-bappeda & admin-opd (lihat
        // PermissionSeeder::DEFAULTS sebelum 2026-10-10), plus super-admin.
        $roleIds = DB::table('roles')
            ->whereIn('slug', ['super-admin', 'admin-bappeda', 'admin-opd'])
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }

        app()['cache']->forget('spatie.permission.cache');
    }

    private function forgetPermission(string $name): void
    {
        $ids = DB::table('permissions')->where('name', $name)->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        app()['cache']->forget('spatie.permission.cache');
    }
};
