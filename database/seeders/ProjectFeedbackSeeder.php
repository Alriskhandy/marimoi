<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProjectFeedback;
use App\Models\UsulanMusrenbang;
use App\Models\ProyekStrategisNasional;
use App\Models\ProyekStrategisDaerah;
use App\Models\PokirDprd;
use App\Models\Lokasi;

class ProjectFeedbackSeeder extends Seeder
{
    public function run()
    {
        // Sample feedback data untuk masing-masing jenis proyek
        $sampleFeedbacks = [
            // Feedback untuk Pokir DPRD
            [
                'feedbackable_type' => PokirDprd::class,
                'feedbackable_id' => 1, // Pastikan ada data Pokir dengan ID 1
                'nama_pemberi_aspirasi' => 'Ahmad Salam',
                'nama_proyek' => 'Pokir Pembangunan Jalan Desa',
                'kabupaten_kota' => 'Ternate',
                'kecamatan' => 'Ternate Selatan',
                'latitude' => 0.7893,
                'longitude' => 127.3774,
                'tanggapan' => 'Usulan Pokir ini sangat bagus untuk kemajuan desa kami. Mohon segera direalisasikan.',
                'jenis_tanggapan' => 'saran',
                'status' => 'pending',
                'email' => 'ahmad.salam@email.com',
                'phone' => '081234567890'
            ],
            
            // Feedback untuk Usulan Musrenbang
            [
                'feedbackable_type' => UsulanMusrenbang::class,
                'feedbackable_id' => 1,
                'nama_pemberi_aspirasi' => 'Siti Rahma',
                'nama_proyek' => 'Usulan Perbaikan Drainase',
                'kabupaten_kota' => 'Tidore Kepulauan',
                'kecamatan' => 'Tidore',
                'latitude' => 0.6848,
                'longitude' => 127.4041,
                'tanggapan' => 'Usulan ini sangat diperlukan karena daerah kami sering banjir saat hujan deras.',
                'jenis_tanggapan' => 'apresiasi',
                'status' => 'ditinjau',
                'email' => 'siti.rahma@email.com',
                'phone' => '081234567891'
            ],
            
            // Feedback untuk Proyek Strategis Nasional
            [
                'feedbackable_type' => ProyekStrategisNasional::class,
                'feedbackable_id' => 1,
                'nama_pemberi_aspirasi' => 'Budi Santoso',
                'nama_proyek' => 'Proyek Jalan Trans Halmahera',
                'kabupaten_kota' => 'Halmahera Barat',
                'kecamatan' => 'Jailolo',
                'tanggapan' => 'Proyek jalan ini sangat strategis untuk menghubungkan wilayah utara dan selatan Halmahera.',
                'jenis_tanggapan' => 'apresiasi',
                'status' => 'ditindaklanjuti',
                'email' => 'budi.santoso@email.com'
            ],
            
            // Feedback untuk Proyek Strategis Daerah
            [
                'feedbackable_type' => ProyekStrategisDaerah::class,
                'feedbackable_id' => 1,
                'nama_pemberi_aspirasi' => 'Maria Karim',
                'nama_proyek' => 'Pengembangan Pelabuhan Perikanan',
                'kabupaten_kota' => 'Halmahera Timur',
                'kecamatan' => 'Maba',
                'tanggapan' => 'Pembangunan pelabuhan ini berjalan lambat. Mohon dipercepat karena musim ikan sudah dekat.',
                'jenis_tanggapan' => 'keluhan',
                'status' => 'pending',
                'email' => 'maria.karim@email.com',
                'phone' => '081234567892'
            ],
            
            // Feedback untuk Lokasi
            [
                'feedbackable_type' => Lokasi::class,
                'feedbackable_id' => 1,
                'nama_pemberi_aspirasi' => 'Yusuf Ibrahim',
                'nama_proyek' => 'Penataan Kawasan Wisata Danau Tolire',
                'kabupaten_kota' => 'Ternate',
                'kecamatan' => 'Ternate Utara',
                'latitude' => 0.8371,
                'longitude' => 127.3737,
                'tanggapan' => 'Penataan kawasan wisata ini perlu melibatkan masyarakat sekitar agar berkelanjutan.',
                'jenis_tanggapan' => 'saran',
                'status' => 'selesai',
                'response_admin' => 'Terima kasih atas sarannya. Tim kami akan melakukan koordinasi dengan masyarakat sekitar.',
                'responded_at' => now()->subDays(5),
                'email' => 'yusuf.ibrahim@email.com'
            ],
            
            // Feedback tambahan untuk variasi data
            [
                'feedbackable_type' => PokirDprd::class,
                'feedbackable_id' => 1,
                'nama_pemberi_aspirasi' => 'Fatimah Usman',
                'nama_proyek' => 'Pokir Bantuan Alat Pertanian',
                'kabupaten_kota' => 'Halmahera Selatan',
                'kecamatan' => 'Bacan',
                'tanggapan' => 'Kapan bantuan alat pertanian dari pokir ini akan disalurkan? Petani sudah menunggu lama.',
                'jenis_tanggapan' => 'pertanyaan',
                'status' => 'ditinjau',
                'phone' => '081234567893'
            ]
        ];

        foreach ($sampleFeedbacks as $feedbackData) {
            ProjectFeedback::create($feedbackData);
        }
    }
}

// ========================================
// COMMAND UNTUK MENJALANKAN MIGRATION
// ========================================

/*
Jalankan perintah berikut di terminal:

1. Buat migration file (jika belum ada):
   php artisan make:migration create_project_feedbacks_table

2. Jalankan migration:
   php artisan migrate

3. Buat seeder (jika ingin data sample):
   php artisan make:seeder ProjectFeedbackSeeder

4. Jalankan seeder:
   php artisan db:seed --class=ProjectFeedbackSeeder

Atau jalankan semua seeder:
   php artisan db:seed
*/

// ========================================
// TROUBLESHOOTING COMMANDS
// ========================================

/*
Jika ada masalah dengan migration:

1. Lihat status migration:
   php artisan migrate:status

2. Rollback migration terakhir:
   php artisan migrate:rollback

3. Rollback semua migration:
   php artisan migrate:reset

4. Fresh migration (hapus semua tabel dan buat ulang):
   php artisan migrate:fresh

5. Fresh migration dengan seeder:
   php artisan migrate:fresh --seed

6. Cek tabel yang ada di database:
   php artisan tinker
   >>> Schema::hasTable('project_feedbacks')
   >>> DB::select('SELECT * FROM information_schema.tables WHERE table_schema = \'public\'')
*/