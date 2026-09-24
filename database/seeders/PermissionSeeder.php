<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Hak akses awal tiap role bawaan. Role yang sudah punya permission
     * (mis. diubah lewat menu Manajemen Role) tidak ditimpa.
     *
     * @var array<string, array<int, string>>
     */
    private const DEFAULTS = [
        'admin-bappeda' => [
            'dashboard.view',
            'data-spatial.*',
            'categories.*',
            'project-feedbacks.*',
            'project-progress.*',
            'dokumen.*',
            'aspirasi.*',
            'kategori-aspirasi.*',
            'opd.*',
            'users.*',
        ],
        'admin-opd' => [
            'dashboard.view',
            'data-spatial.*',
            'categories.view',
            'project-feedbacks.view',
            'project-feedbacks.respond',
            'project-progress.view',
            'project-progress.create',
            'project-progress.edit',
            'aspirasi.view',
            'aspirasi.edit',
            'aspirasi.export',
        ],
        'user' => [
            'dashboard.view',
        ],
    ];

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
