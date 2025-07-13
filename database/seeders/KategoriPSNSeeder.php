<?php

namespace Database\Seeders;

use App\Models\KategoriPSN;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KategoriPSNSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
   public function run(): void
{
   $kategoris = [
        'Pusat Pertumbuhan Ekonomi',
        'Kawasan Industri',
        'Kawasan Strategis Pariwisata',
        'Pengembangan Infrastruktur Wilayah',
        'Kawasan Perkotaan',
        'Kawasan Pedesaan',
        'Kawasan Rawan Bencana',
        'Kawasan Hutan dan Konservasi',
        'Kawasan Perbatasan',
        'Kawasan Transmigrasi',
    ];

    foreach ($kategoris as $nama) {
        KategoriPSN::create([
            'nama' => $nama,
            'warna' => 'green', // Disarankan diganti nanti dengan kode warna hex atau nama warna yang valid
            'parent_id' => null,
            'deskripsi' => null,
        ]);
    }
}
}
