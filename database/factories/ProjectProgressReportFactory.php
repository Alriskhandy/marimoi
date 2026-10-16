<?php

namespace Database\Factories;

use App\Models\DataSpatial;
use App\Models\ProjectProgressReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectProgressReportFactory extends Factory
{
    protected $model = ProjectProgressReport::class;

    public function definition(): array
    {
        $dataSpatial = DataSpatial::factory()->create(['data_type' => 'proyek_strategis', 'sub_type' => 'psd']);

        return [
            'data_spatial_id' => $dataSpatial->id,
            'opd_id' => $dataSpatial->opd_pengelola_id,
            'kategori_id' => $dataSpatial->kategori_id,
            'tahun_anggaran' => now()->year,
            'periode_laporan' => $this->faker->randomElement(ProjectProgressReport::PERIODE),
            'pagu' => $this->faker->randomFloat(2, 100000000, 5000000000),
            'realisasi_anggaran' => $this->faker->randomFloat(2, 0, 3000000000),
            'progres_fisik_persen' => $this->faker->randomFloat(2, 0, 100),
            'status' => $this->faker->randomElement(ProjectProgressReport::STATUSES),
            'catatan' => null,
            'dilaporkan_oleh' => User::factory(),
        ];
    }
}
