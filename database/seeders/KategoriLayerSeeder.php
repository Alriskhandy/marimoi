<?php

namespace Database\Seeders;

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
        $kategoris = [
            'Ekonomi',
            'Infrastruktur',
            'Kemiskinan',
            'Kependudukan',
            'Kesehatan',
            'Lingkungan Hidup',
            'Pariwisata & Kebudayaan',
            'Pendidikan',
            'Sosial',
            'Peta Dasar',
        ];

        foreach ($kategoris as $nama) {
            KategoriLayer::create([
                'nama' => $nama,
                'warna' => 'blue',
                'parent_id' => null,
                'deskripsi' => null,
            ]);
        }
    }
}
