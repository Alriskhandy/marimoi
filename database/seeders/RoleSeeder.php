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
                'description' => 'Administrator dengan akses penuh ke sistem',
            ],
            [
                'name' => 'Admin OPD',
                'description' => 'Administrator untuk OPD tertentu',
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}