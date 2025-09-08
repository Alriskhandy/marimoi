<?php

namespace Database\Factories;

use App\Models\DataSpatial;
use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class DataSpatialFactory extends Factory
{
    protected $model = DataSpatial::class;

    public function definition(): array
    {
        // Bounding box Maluku Utara
        $lat = $this->faker->randomFloat(6, -2.0, 3.0);     // latitude
        $lng = $this->faker->randomFloat(6, 126.0, 129.5);  // longitude

        return [
            'user_id' => User::inRandomOrder()->first()?->id,
            'uuid' => $this->faker->uuid,
            'data_type' => 'tematik',  // fix "tematik"
            'sub_type' => null,        // fix null
            'gambar' => $this->faker->imageUrl(),
            'kategori_id' => Category::inRandomOrder()->first()?->id,
            'deskripsi' => $this->faker->sentence(10),
            'dbf_attributes' => json_encode([
                'kode' => Str::random(5),
                'nilai' => $this->faker->numberBetween(100, 9999),
            ]),
            'tahun' => $this->faker->numberBetween(2015, 2025),
            'views' => $this->faker->numberBetween(0, 500),
            // Point geometry (lon, lat)
            'geom' => DB::raw("ST_SetSRID(ST_MakePoint($lng, $lat), 4326)"),
        ];
    }
}
