<?php

namespace Database\Seeders;

use App\Models\Aspirasi;
use App\Models\CategoriesAspirasi;
use App\Models\KategoriAspirasi;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AspirasiSeeder extends Seeder
{
    public function run(): void
    {
        $categories = KategoriAspirasi::all();
        $admin = User::where('email', 'admin@admin.com')->first();

        $aspirasi_data = [
            [
                'kategori_aspirasi_id' => 1,
                'nama_pengirim' => 'Ahmad Sudrajat',
                'email' => 'ahmad.sudrajat@gmail.com',
                'phone' => '08123456789',
                'alamat' => 'Jl. Kebon Jeruk No. 15, Jakarta Barat',
                'jenis_aspirasi' => 'usulan',
                'judul_aspirasi' => 'Jalan Berlubang di Kebon Jeruk',
                'isi_aspirasi' => 'Jalan di depan rumah saya sudah berlubang besar sejak 2 bulan yang lalu. Mohon segera diperbaiki karena membahayakan pengendara.',
                'status' => 'pending',
                'latitude' => -6.1889,
                'longitude' => 106.7778
            ],
            [
                'kategori_aspirasi_id' => 1,
                'nama_pengirim' => 'Siti Nurhaliza',
                'email' => 'siti.nurhaliza@gmail.com',
                'phone' => '08234567890',
                'alamat' => 'Jl. Cempaka Putih No. 25, Jakarta Pusat',
                'jenis_aspirasi' => 'usulan',
                'judul_aspirasi' => 'Pelayanan Puskesmas Lambat',
                'isi_aspirasi' => 'Pelayanan di Puskesmas Cempaka Putih sangat lambat. Antrian panjang dan hanya ada 1 dokter yang bertugas.',
                'status' => 'diproses',
                'admin_id' => $admin?->id,
                'tanggal_respon' => Carbon::now()->subDays(2),
                'tanggapan_admin' => 'Terima kasih atas laporannya. Kami sedang meninjau dan akan menambah tenaga medis.'
            ],
            ['kategori_aspirasi_id' => 1,
                'nama_pengirim' => 'Budi Santoso',
                'email' => 'budi.santoso@gmail.com',
                'phone' => '08345678901',
                'alamat' => 'Jl. Sudirman No. 100, Jakarta Selatan',
                'jenis_aspirasi' => 'usulan',
                'judul_aspirasi' => 'Perbaikan Website Pemda',
                'isi_aspirasi' => 'Website pemda sering error dan loading lambat. Mohon diperbaiki agar masyarakat dapat mengakses informasi dengan baik.',
                'status' => 'selesai',
                'admin_id' => $admin?->id,
                'tanggal_respon' => Carbon::now()->subDays(7),
                'tanggapan_admin' => 'Website telah diperbaiki dan dioptimasi. Terima kasih atas masukannya.'
            ],
            [
                'kategori_aspirasi_id' => 1,
                'nama_pengirim' => 'Dewi Lestari',
                'email' => 'dewi.lestari@gmail.com',
                'phone' => '08456789012',
                'alamat' => 'Jl. Pendidikan No. 50, Jakarta Timur',
                'jenis_aspirasi' => 'usulan',
                'judul_aspirasi' => 'Penambahan Fasilitas Lab Komputer',
                'isi_aspirasi' => 'SD Negeri 01 membutuhkan penambahan komputer di lab. Saat ini hanya ada 10 komputer untuk 35 siswa per kelas.',
                'status' => 'pending',
            ],
            [
                'kategori_aspirasi_id' => 1,
                'nama_pengirim' => 'Eko Prasetyo',
                'email' => 'eko.prasetyo@gmail.com',
                'phone' => '08567890123',
                'alamat' => 'Jl. Banjir Kanal No. 75, Jakarta Utara',
                'jenis_aspirasi' => 'kritik & saran',
                'judul_aspirasi' => 'Saluran Air Tersumbat',
                'isi_aspirasi' => 'Saluran air di komplek perumahan tersumbat sampah. Setiap hujan pasti banjir. Mohon segera dibersihkan.',
                'status' => 'diproses',
                'admin_id' => $admin?->id,
                'tanggal_respon' => Carbon::now()->subDays(1),
                'tanggapan_admin' => 'Tim akan segera ke lokasi untuk pembersihan saluran air.'
            ]
        ];

        foreach ($aspirasi_data as $data) {
            Aspirasi::create($data);
        }
    }
}