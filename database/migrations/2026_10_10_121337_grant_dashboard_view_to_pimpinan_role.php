<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Role "Pimpinan" melihat Dashboard versi eksekutif (lihat App\Support\DashboardMetrics).
 * Role dibuat bila belum ada, lalu diberi dashboard.view agar bisa membuka dashboard.
 */
return new class extends Migration
{
    public function up(): void
    {
        $roleId = DB::table('roles')->where('slug', 'pimpinan')->value('id')
            ?? DB::table('roles')->insertGetId([
                'name' => 'Pimpinan',
                'slug' => 'pimpinan',
                'description' => 'Pimpinan daerah/Bappeda: ringkasan eksekutif dashboard',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $permissionId = DB::table('permissions')->where('name', 'dashboard.view')->value('id')
            ?? DB::table('permissions')->insertGetId([
                'name' => 'dashboard.view',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $roleId]);

        app()['cache']->forget('spatie.permission.cache');
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('slug', 'pimpinan')->value('id');
        $permissionId = DB::table('permissions')->where('name', 'dashboard.view')->value('id');

        if ($roleId && $permissionId) {
            DB::table('role_has_permissions')->where(['permission_id' => $permissionId, 'role_id' => $roleId])->delete();
        }

        app()['cache']->forget('spatie.permission.cache');
    }
};
