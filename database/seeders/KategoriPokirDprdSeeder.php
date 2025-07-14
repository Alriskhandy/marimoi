<?php

namespace Database\Seeders;

use App\Models\KategoriPokirDprd;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KategoriPokirDprdSeeder extends Seeder
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
            KategoriPokirDprd::create([
                'nama' => $nama,
                'warna' => 'blue',
                'parent_id' => null,
                'deskripsi' => null,
            ]);
        }
    }
}
