<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Permission tambahan yang WAJIB dimiliki role ini, ditambahkan secara
     * aditif (tidak menimpa/menghapus permission lain milik role) setiap kali
     * seeder ini jalan — berbeda dari DEFAULTS yang hanya berlaku sekali untuk
     * role yang belum punya permission apa pun sama sekali.
     *
     * Dipakai untuk `spatial-layers.*` (modul "Daftar Layer & Data" v3):
     * role `admin-bappeda`/`admin-opd` yang sudah lama di-seed dengan
     * permission lain (mis. `data-spatial.*`) tidak akan pernah lolos guard
     * DEFAULTS, padahal keduanya wajib bisa memakai modul baru ini
     * (lihat docs/marimoi v2/spec-admin-manajemen-peta.md §2, R17, R19–R21).
     *
     * @var array<string, array<int, string>>
     */
    private const ADDITIONAL_GRANTS = [
        'admin-bappeda' => [
            'spatial-layers.view',
            'spatial-layers.create',
            'spatial-layers.edit',
            'spatial-layers.delete',
            'spatial-layers.publish',
            'document-templates.view',
            'document-templates.manage',
        ],
        'admin-opd' => [
            'spatial-layers.view',
            'spatial-layers.create',
            'spatial-layers.edit',
            'spatial-layers.delete',
        ],
        'pimpinan' => [
            'dashboard.view',
        ],
    ];

    /**
     * Hak akses awal tiap role bawaan. Role yang sudah punya permission
     * (mis. diubah lewat menu Manajemen Role) tidak ditimpa.
     *
     * @var array<string, array<int, string>>
     */
    private const DEFAULTS = [
        'admin-bappeda' => [
            'dashboard.view',
            'categories.*',
            'project-feedbacks.*',
            'aspirasi.*',
            'kategori-aspirasi.*',
            'opd.*',
            'users.*',
        ],
        'admin-opd' => [
            'dashboard.view',
            'categories.view',
            'project-feedbacks.view',
            'project-feedbacks.respond',
            'aspirasi.view',
            'aspirasi.edit',
            'aspirasi.export',
        ],
        'user' => [
            'dashboard.view',
        ],
    ];

    /**
     * Permission yang sudah dihapus dari katalog tapi mungkin masih
     * tersimpan di DB (role lama yang sudah pernah di-seed) — dicabut permanen
     * dari role yang memegangnya lalu baris-nya sendiri dihapus.
     *
     * - data-spatial.   : Fase I/D13, modul "Data Spasial" lama di-retire.
     * - map-types.      : 2026-10-10, modul "Jenis Peta" dihapus utuh.
     * - project-progress.: 2026-10-10, halaman "Dashboard Pembangunan" dihapus.
     *
     * @var array<int, string>
     */
    private const RETIRED_PREFIXES = ['data-spatial.', 'map-types.', 'project-progress.'];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $names = Permission::catalogNames();

        foreach ($names as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        Role::where('slug', 'super-admin')->each(fn (Role $role) => $role->syncPermissions($names));

        foreach (self::DEFAULTS as $slug => $patterns) {
            $role = Role::where('slug', $slug)->first();

            if (! $role || $role->permissions()->exists()) {
                continue;
            }

            $role->syncPermissions($this->expand($patterns, $names));
        }

        foreach (self::ADDITIONAL_GRANTS as $slug => $permissionNames) {
            $role = Role::where('slug', $slug)->first();

            if (! $role) {
                continue;
            }

            foreach ($permissionNames as $name) {
                if (! $role->hasPermissionTo($name)) {
                    $role->givePermissionTo($name);
                }
            }
        }

        foreach (self::RETIRED_PREFIXES as $prefix) {
            Permission::where('name', 'like', $prefix.'%')->get()->each(function (Permission $permission) {
                $permission->roles()->detach();
                $permission->delete();
            });
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param  array<int, string>  $patterns
     * @param  array<int, string>  $names
     * @return array<int, string>
     */
    private function expand(array $patterns, array $names): array
    {
        $expanded = [];

        foreach ($patterns as $pattern) {
            foreach ($names as $name) {
                if ($pattern === $name || (str_ends_with($pattern, '.*') && str_starts_with($name, substr($pattern, 0, -1)))) {
                    $expanded[] = $name;
                }
            }
        }

        return array_values(array_unique($expanded));
    }
}
