<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Publication;
use App\Models\PublicationDownload;
use Carbon\Carbon;

class PublicationDownloadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all publications
        $publications = Publication::all();

        if ($publications->isEmpty()) {
            $this->command->warn('No publications found. Please run PublicationSeeder first.');
            return;
        }

        // Sample data for downloads
        $sampleDownloads = [
            [
                'name' => 'Dr. Ahmad Wijaya',
                'email' => 'ahmad.wijaya@universitas.ac.id',
                'phone' => '081234567890',
                'organization' => 'Universitas Negeri Jakarta',
                'position' => 'Dosen/Peneliti',
                'purpose' => 'Penelitian Akademik',
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'downloaded_at' => Carbon::now()->subDays(5)->subHours(2),
            ],
            [
                'name' => 'Siti Nurhaliza',
                'email' => 'siti.nurhaliza@bappeda.go.id',
                'phone' => '081987654321',
                'organization' => 'Bappeda Kota Bekasi',
                'position' => 'Staf Perencanaan',
                'purpose' => 'Referensi Perencanaan',
                'ip_address' => '10.10.1.50',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                'downloaded_at' => Carbon::now()->subDays(3)->subHours(5),
            ],
            [
                'name' => 'Budi Santoso',
                'email' => 'budi.santoso@gmail.com',
                'phone' => '082123456789',
                'organization' => 'PT. Konsultan Pembangunan',
                'position' => 'Senior Consultant',
                'purpose' => 'Studi Kelayakan',
                'ip_address' => '203.142.4.27',
                'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
                'downloaded_at' => Carbon::now()->subDays(2)->subHours(3),
            ],
            [
                'name' => 'Maya Sari Dewi',
                'email' => 'maya.dewi@mahasiswa.ac.id',
                'phone' => '085678901234',
                'organization' => 'Institut Teknologi Bandung',
                'position' => 'Mahasiswa S2',
                'purpose' => 'Tugas Akhir/Tesis',
                'ip_address' => '114.79.32.15',
                'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1 like Mac OS X) AppleWebKit/605.1.15',
                'downloaded_at' => Carbon::now()->subDays(1)->subHours(8),
            ],
        ];

        // Create download records for each publication
        foreach ($publications as $index => $publication) {
            if (isset($sampleDownloads[$index])) {
                $downloadData = $sampleDownloads[$index];
                $downloadData['publication_id'] = $publication->id;

                PublicationDownload::create($downloadData);

                $this->command->info("Download record created for publication: {$publication->title}");
            }
        }

        // Add some additional random downloads for variety
        $this->createAdditionalDownloads($publications);

        $this->command->info('Publication downloads seeded successfully!');
    }

    /**
     * Create additional random downloads for more variety
     */
    private function createAdditionalDownloads($publications): void
    {
        $additionalDownloads = [
            [
                'name' => 'Prof. Indira Sari',
                'email' => 'indira.sari@ui.ac.id',
                'phone' => '081567890123',
                'organization' => 'Universitas Indonesia',
                'position' => 'Profesor',
                'purpose' => 'Penelitian',
                'ip_address' => '152.118.24.10',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ],
            [
                'name' => 'Rizki Pratama',
                'email' => 'rizki.pratama@pemkot.go.id',
                'phone' => '082345678901',
                'organization' => 'Pemkot Tangerang',
                'position' => 'Analis Kebijakan',
                'purpose' => 'Analisis Kebijakan',
                'ip_address' => '36.79.118.45',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            ],
            [
                'name' => 'Dewi Kusuma',
                'email' => 'dewi.kusuma@lsm.org',
                'phone' => null,
                'organization' => 'LSM Peduli Lingkungan',
                'position' => 'Koordinator Program',
                'purpose' => 'Advokasi Lingkungan',
                'ip_address' => '180.242.212.10',
                'user_agent' => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64) AppleWebKit/537.36',
            ],
            [
                'name' => 'Andi Firmansyah',
                'email' => 'andi.firmansyah@journalist.com',
                'phone' => '087654321098',
                'organization' => 'Media Online Berita',
                'position' => 'Jurnalis',
                'purpose' => 'Liputan Berita',
                'ip_address' => '103.28.148.73',
                'user_agent' => 'Mozilla/5.0 (Android 11; Mobile; rv:68.0) Gecko/68.0 Firefox/88.0',
            ],
        ];

        // Randomly assign these downloads to publications
        foreach ($additionalDownloads as $downloadData) {
            $randomPublication = $publications->random();
            $downloadData['publication_id'] = $randomPublication->id;
            $downloadData['downloaded_at'] = Carbon::now()->subDays(rand(1, 7))->subHours(rand(1, 23));

            PublicationDownload::create($downloadData);
        }

        // Create a few more downloads for the most popular publication
        $popularPublication = $publications->first();
        $quickDownloads = [
            [
                'name' => 'Lisa Anggraini',
                'email' => 'lisa.anggraini@student.ac.id',
                'phone' => '089123456789',
                'organization' => 'Universitas Airlangga',
                'position' => 'Mahasiswa',
                'purpose' => 'Skripsi',
                'ip_address' => '125.166.48.92',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
                'downloaded_at' => Carbon::now()->subHours(6),
            ],
            [
                'name' => 'Hendra Wijayanto',
                'email' => 'hendra.w@konsultan.co.id',
                'phone' => '081876543210',
                'organization' => 'CV. Solusi Pembangunan',
                'position' => 'Direktur',
                'purpose' => 'Proposal Proyek',
                'ip_address' => '202.43.168.25',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36',
                'downloaded_at' => Carbon::now()->subHours(3),
            ],
        ];

        foreach ($quickDownloads as $downloadData) {
            $downloadData['publication_id'] = $popularPublication->id;
            PublicationDownload::create($downloadData);
        }
    }
}
