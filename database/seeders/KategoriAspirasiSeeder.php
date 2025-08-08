<?php

namespace Database\Seeders;

use App\Models\CategoriesAspirasi;
use App\Models\KategoriAspirasi;
use App\Models\Opd;
use Illuminate\Database\Seeder;

class KategoriAspirasiSeeder extends Seeder
{
    public function run(): void
    {
        $diskominfo = Opd::where('singkatan', 'DISKOMINFO')->first();
        $dpupr = Opd::where('singkatan', 'DPUPR')->first();
        $dinkes = Opd::where('singkatan', 'DINKES')->first();
        $disdik = Opd::where('singkatan', 'DISDIK')->first();
        $dinsos = Opd::where('singkatan', 'DINSOS')->first();

        $categories = [
            // DISKOMINFO
            [
                'nama_kategori' => 'Kritik dan Saran',
                'deskripsi' => 'Kritik dan saran terkait website marimoi',
            ],
            [
                'opd_id' => $diskominfo->id,
                'nama_kategori' => 'Website dan Aplikasi',
                'deskripsi' => 'Masalah terkait website dan aplikasi pemerintah',
            ],
            [
                'opd_id' => $diskominfo->id,
                'nama_kategori' => 'Infrastruktur IT',
                'deskripsi' => 'Masalah infrastruktur teknologi informasi',
            ],
            
            // DPUPR
            [
                'opd_id' => $dpupr->id,
                'nama_kategori' => 'Jalan dan Jembatan',
                'deskripsi' => 'Masalah jalan rusak, jembatan, dan infrastruktur transportasi',
            ],
            [
                'opd_id' => $dpupr->id,
                'nama_kategori' => 'Drainase dan Banjir',
                'deskripsi' => 'Masalah drainase, banjir, dan pengelolaan air',
            ],
            
            // DINKES
            [
                'opd_id' => $dinkes->id,
                'nama_kategori' => 'Pelayanan Kesehatan',
                'deskripsi' => 'Keluhan pelayanan di fasilitas kesehatan',
            ],
            [
                'opd_id' => $dinkes->id,
                'nama_kategori' => 'Lingkungan Sehat',
                'deskripsi' => 'Masalah kebersihan dan kesehatan lingkungan',
            ],
            
            // DISDIK
            [
                'opd_id' => $disdik->id,
                'nama_kategori' => 'Fasilitas Sekolah',
                'deskripsi' => 'Masalah fasilitas dan infrastruktur sekolah',
            ],
            [
                'opd_id' => $disdik->id,
                'nama_kategori' => 'Tenaga Pendidik',
                'deskripsi' => 'Masalah terkait guru dan tenaga pendidik',
            ],
            
            // DINSOS
            [
                'opd_id' => $dinsos->id,
                'nama_kategori' => 'Bantuan Sosial',
                'deskripsi' => 'Masalah bantuan sosial dan kemiskinan',
            ],
            [
                'opd_id' => $dinsos->id,
                'nama_kategori' => 'Anak dan Lansia',
                'deskripsi' => 'Masalah perlindungan anak dan lansia',
            ]
        ];

        foreach ($categories as $category) {
            KategoriAspirasi::create($category);
        }
    }
}