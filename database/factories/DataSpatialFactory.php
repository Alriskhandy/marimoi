<?php

namespace Database\Factories;

use App\Models\DataSpatial;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class DataSpatialFactory extends Factory
{
    protected $model = DataSpatial::class;

    public function definition(): array
    {
        // Daftar jenis usulan musrenbang yang realistis
        $jenisUsulan = [
            'Pembangunan Jalan Desa',
            'Perbaikan Jembatan',
            'Pembangunan Puskesmas',
            'Renovasi Sekolah Dasar',
            'Pembangunan Balai Desa',
            'Perbaikan Saluran Irigasi',
            'Pembangunan Pasar Tradisional',
            'Perbaikan Jalan Lingkungan',
            'Pembangunan Posyandu',
            'Renovasi Masjid',
            'Pembangunan MCK Umum',
            'Perbaikan Drainase',
            'Pembangunan Taman',
            'Perbaikan Fasilitas Olahraga',
            'Pembangunan Perpustakaan Desa'
        ];

        $kecamatanMalut = [
            'Ternate Utara', 'Ternate Selatan', 'Ternate Tengah', 'Ternate Barat',
            'Tidore', 'Tidore Timur', 'Tidore Selatan', 'Tidore Utara',
            'Jailolo', 'Jailolo Selatan', 'Sahu', 'Sahu Timur',
            'Kao', 'Kao Utara', 'Kao Barat', 'Kao Teluk',
            'Morotai Selatan', 'Morotai Utara', 'Morotai Timur', 'Morotai Jaya'
        ];

        $statusPrioritas = ['Tinggi', 'Sedang', 'Rendah'];
        $statusUsulan = ['Usulan Baru', 'Dalam Review', 'Disetujui', 'Ditolak', 'Dalam Pelaksanaan'];

        return [
            'user_id' => 1, // Fixed user ID
            'uuid' => Str::uuid(),
            'data_type' => 'usulan_musrenbang', // Fixed data type
            'sub_type' => null,
            'gambar' => $this->faker->optional(0.3)->imageUrl(800, 600, 'business'),
            'kategori_id' => 1, // Fixed category ID
            'deskripsi' => $this->faker->paragraph(2),
            'dbf_attributes' => [
                'NAMA' => $this->faker->randomElement($jenisUsulan),
                'KODE_USULAN' => $this->faker->bothify('MUS-####-??'),
                'ALAMAT' => $this->faker->streetAddress(),
                'KECAMATAN' => $this->faker->randomElement($kecamatanMalut),
                'KELURAHAN' => $this->faker->city() . ' ' . $this->faker->randomElement(['Utara', 'Selatan', 'Timur', 'Barat', 'Tengah']),
                'USULAN' => $this->faker->randomElement($jenisUsulan) . ' di ' . $this->faker->streetName(),
                'PRIORITAS' => $this->faker->randomElement($statusPrioritas),
                'STATUS' => $this->faker->randomElement($statusUsulan),
                'ANGGARAN' => $this->faker->numberBetween(25000000, 800000000), // 25jt - 800jt
                'PENGUSUL' => $this->faker->name(),
                'KONTAK' => $this->faker->phoneNumber(),
                'TAHUN_USULAN' => $this->faker->numberBetween(2023, 2025),
                'BIDANG' => $this->faker->randomElement([
                    'Infrastruktur', 'Pendidikan', 'Kesehatan', 'Ekonomi', 
                    'Sosial', 'Lingkungan', 'Pertanian', 'Perikanan'
                ]),
                'VOLUME' => $this->faker->numberBetween(1, 500) . ' ' . 
                           $this->faker->randomElement(['meter', 'unit', 'paket', 'titik', 'buah']),
                'KETERANGAN' => $this->faker->sentence(8),
                'KOORDINATOR' => $this->faker->name(),
                'RAPAT_KE' => $this->faker->numberBetween(1, 5),
            ],
            'tahun' => $this->faker->numberBetween(2023, 2025),
            'views' => $this->faker->numberBetween(0, 150),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => function (array $attributes) {
                return $this->faker->dateTimeBetween($attributes['created_at'], 'now');
            },
        ];
    }

    /**
     * Configure the model factory with PostGIS geometry
     */
    public function configure(): static
    {
        return $this->afterCreating(function (DataSpatial $dataSpatial) {
            // Generate random coordinates specifically for Maluku Utara region
            // More precise coordinates for major areas in Maluku Utara:
            $coordinates = $this->getRandomMalukuUtaraCoordinates();
            
            // Update with PostGIS geometry
            DB::statement(
                "UPDATE data_spatial SET geom = ST_GeomFromText(?, 4326) WHERE id = ?",
                ["POINT({$coordinates['lng']} {$coordinates['lat']})", $dataSpatial->id]
            );
        });
    }

    /**
     * Get random coordinates within Maluku Utara specific areas
     */
    private function getRandomMalukuUtaraCoordinates(): array
    {
        $areas = [
            // Ternate Island
            ['lat_min' => 0.7, 'lat_max' => 0.85, 'lng_min' => 127.3, 'lng_max' => 127.45],
            // Tidore Island  
            ['lat_min' => 0.6, 'lat_max' => 0.75, 'lng_min' => 127.35, 'lng_max' => 127.5],
            // Halmahera Barat
            ['lat_min' => 0.5, 'lat_max' => 1.5, 'lng_min' => 127.0, 'lng_max' => 128.0],
            // Halmahera Utara
            ['lat_min' => 1.2, 'lat_max' => 2.0, 'lng_min' => 127.5, 'lng_max' => 128.5],
            // Morotai
            ['lat_min' => 2.0, 'lat_max' => 2.4, 'lng_min' => 128.2, 'lng_max' => 128.5],
        ];

        $selectedArea = $this->faker->randomElement($areas);
        
        return [
            'lat' => $this->faker->randomFloat(6, $selectedArea['lat_min'], $selectedArea['lat_max']),
            'lng' => $this->faker->randomFloat(6, $selectedArea['lng_min'], $selectedArea['lng_max']),
        ];
    }
}