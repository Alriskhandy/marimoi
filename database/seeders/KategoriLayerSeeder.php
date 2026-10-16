<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class KategoriLayerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            // Layers (Lokasi)
            ['nama' => 'Pendidikan', 'deskripsi' => 'Lokasi sekolah dan kampus'],
            ['nama' => 'Kesehatan', 'deskripsi' => 'Fasilitas layanan kesehatan'],

            // Musrenbang
            ['nama' => 'Infrastruktur', 'deskripsi' => 'Usulan pembangunan infrastruktur'],
            ['nama' => 'Ekonomi', 'deskripsi' => 'Usulan sektor ekonomi'],

            // Pokir DPRD
            ['nama' => 'Fasilitas Umum', 'deskripsi' => 'Program Pokir terkait fasilitas umum'],

            // Proyek Strategis Daerah (PSD)
            ['nama' => 'Jalan Provinsi', 'deskripsi' => 'Pembangunan jalan provinsi'],
            ['nama' => 'Irigasi', 'deskripsi' => 'Proyek irigasi daerah'],

            // Proyek Strategis Nasional (PSN)
            ['nama' => 'Bandara', 'deskripsi' => 'Pembangunan bandara nasional'],
            ['nama' => 'Pelabuhan', 'deskripsi' => 'Proyek pelabuhan nasional'],
        ];

        foreach ($data as $item) {
            Category::create($item);
        }
    }
}
