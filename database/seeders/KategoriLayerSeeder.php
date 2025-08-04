<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\KategoriLayer;

class KategoriLayerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
 public function run(): void
    {
        $data = [
            // Layers (Lokasi)
            ['type' => 'tematik', 'nama' => 'Pendidikan', 'warna' => '#FF5733', 'icon' => null, 'is_marker' => true, 'deskripsi' => 'Lokasi sekolah dan kampus'],
            ['type' => 'tematik', 'nama' => 'Kesehatan', 'warna' => '#33C1FF', 'icon' => null, 'is_marker' => true, 'deskripsi' => 'Fasilitas layanan kesehatan'],

            // Musrenbang
            ['type' => 'usulan_musrenbang', 'nama' => 'Infrastruktur', 'warna' => '#FFC300', 'icon' => null, 'is_marker' => false, 'deskripsi' => 'Usulan pembangunan infrastruktur'],
            ['type' => 'usulan_musrenbang', 'nama' => 'Ekonomi', 'warna' => '#DAF7A6', 'icon' => null, 'is_marker' => false, 'deskripsi' => 'Usulan sektor ekonomi'],

            // Pokir DPRD
            ['type' => 'pokir_dprd', 'nama' => 'Fasilitas Umum', 'warna' => '#C70039', 'icon' => null, 'is_marker' => false, 'deskripsi' => 'Program Pokir terkait fasilitas umum'],

            // Proyek Strategis Daerah (PSD)
            ['type' => 'psd', 'nama' => 'Jalan Provinsi', 'warna' => '#900C3F', 'icon' => null, 'is_marker' => false, 'deskripsi' => 'Pembangunan jalan provinsi'],
            ['type' => 'psd', 'nama' => 'Irigasi', 'warna' => '#581845', 'icon' => null, 'is_marker' => false, 'deskripsi' => 'Proyek irigasi daerah'],

            // Proyek Strategis Nasional (PSN)
            ['type' => 'psn', 'nama' => 'Bandara', 'warna' => '#28A745', 'icon' => null, 'is_marker' => false, 'deskripsi' => 'Pembangunan bandara nasional'],
            ['type' => 'psn', 'nama' => 'Pelabuhan', 'warna' => '#6C757D', 'icon' => null, 'is_marker' => false, 'deskripsi' => 'Proyek pelabuhan nasional'],
        ];

        foreach ($data as $item) {
            Category::create($item);
        }
    }
}
