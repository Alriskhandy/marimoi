<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Super Admin',
                'slug' => 'super-admin',
                'description' => 'Administrator dengan akses penuh ke sistem',
            ],
            [
                'name' => 'Admin Bappeda',
                'slug' => 'admin-bappeda',
                'description' => 'Administrator dengan akses tertentu ke sistem',
            ],
            [
                'name' => 'Admin OPD',
                'slug' => 'admin-opd',
                'description' => 'Administrator untuk OPD tertentu',
            ],
            [
                'name' => 'User',
                'slug' => 'user',
                'description' => 'Pengguna publik hasil login Google',
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
