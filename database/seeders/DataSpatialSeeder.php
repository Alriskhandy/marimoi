<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DataSpatial;

class DataSpatialSeeder extends Seeder
{
    public function run(): void
    {
        // Generate 5000 data dummy
        DataSpatial::factory()->count(10000)->create();
    }
}
