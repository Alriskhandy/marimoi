<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProjectFeedbackSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('project_feedbacks')->insert([
            [
                'data_spatial_id' => null,
                'nama_pemberi_aspirasi' => 'Ahmad Yusuf',
                'nama_proyek' => 'Pembangunan Jalan Desa',
                'kabupaten_kota' => 'Kabupaten Sleman',
                'kecamatan' => 'Ngaglik',
                'latitude' => -7.747034,
                'longitude' => 110.355398,
                'laporan_gambar' => null,
                'tanggapan' => 'Jalan rusak dan berlubang, mohon segera diperbaiki.',
                'jenis_tanggapan' => 'keluhan',
                'status' => 'pending',
                'email' => 'ahmad@example.com',
                'phone' => '081234567890',
                'response_admin' => null,
                'responded_at' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'data_spatial_id' => null,
                'nama_pemberi_aspirasi' => 'Siti Nurhaliza',
                'nama_proyek' => 'Taman Kota',
                'kabupaten_kota' => 'Kota Yogyakarta',
                'kecamatan' => 'Gondokusuman',
                'latitude' => -7.782889,
                'longitude' => 110.367103,
                'laporan_gambar' => null,
                'tanggapan' => 'Taman kota sudah rapi dan bersih, terima kasih.',
                'jenis_tanggapan' => 'apresiasi',
                'status' => 'selesai',
                'email' => 'siti@example.com',
                'phone' => '082345678901',
                'response_admin' => 'Terima kasih atas apresiasinya.',
                'responded_at' => Carbon::now(),
                'created_at' => Carbon::now()->subDays(2),
                'updated_at' => Carbon::now()->subDay(),
            ],
        ]);
    }
}
