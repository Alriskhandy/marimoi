<?php

namespace Database\Seeders;

use App\Models\KategoriPSD;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KategoriPSDSeeder extends Seeder
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
            KategoriPSD::create([
                'nama' => $nama,
                'warna' => $nama,
                'parent_id' => null,
                'deskripsi' => null,
            ]);
        }
    }
}
