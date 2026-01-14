<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DataSpatial;

class MusrenbangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 100 random Musrenbang data records
        DataSpatial::factory()->count(100)->create();
        
        $this->command->info('✅ Successfully created 100 Musrenbang data records');
    }
}