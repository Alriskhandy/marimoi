<?php

namespace Database\Seeders;

use App\Models\KategoriMusrenbang;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KategoriMusrenbangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kategoris = [
        'Pendidikan berkualitas dan merata' => [
            'Pembangunan Prasarana SMA/SMK/SLB',
            'Rehabilitasi Prasarana SMA/SMK/SLB',
            'Pengadaan Sarana Pendidikan',
        ],
        'Kesehatan Untuk Semua' => [
            'Pembangunan/Rehabilitasi/Penyediaan Sarana & Prasarana Rumah Sakit Daerah',
        ],
        'Layanan infrastruktur dasar berkualitas dan merata' => [
            'Pembangunan Jalan dan Jembatan',
        ],
    ];

    foreach ($kategoris as $parentNama => $subKategoris) {
        $parent = KategoriMusrenbang::create([
            'nama' => $parentNama,
            'warna' => '#d6e9c6', // warna hijau muda (parent)
            'parent_id' => null,
            'deskripsi' => null,
        ]);

        foreach ($subKategoris as $subNama) {
            KategoriMusrenbang::create([
                'nama' => $subNama,
                'warna' => '#f9c6aa', // warna merah muda (sub)
                'parent_id' => $parent->id,
                'deskripsi' => null,
            ]);
        }
    }
    }
}
