<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $this->call([
                KategoriLayerSeeder::class,
                ProjectFeedbackSeeder::class,
                RoleSeeder::class,
                OpdSeeder::class,
                KategoriAspirasiSeeder::class,
                AspirasiSeeder::class,
            ]);

             User::create([
            'name' => 'Super Admin',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('admin123')
        ]);
           
        // User::create([
        //     'name' => 'Super Admin',
        //     'role_id' => 1,
        //     'email' => 'admin@gmail.com',
        //     'password' => bcrypt('admin123')
        // ]);
        // User::create([
        //     'name' => 'BAPPEDA',
        //     'role_id' => 2,
        //     'email' => 'bappeda@gmail.com',
        //     'password' => bcrypt('admin123')
        // ]);
        // User::create([
        //     'name' => 'DISPAR',
        //     'role_id' => 3,
        //     'email' => 'opd@gmail.com',
        //     'password' => bcrypt('admin123')
        // ]);

        
    }
}
