<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Publication;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PublicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {



        $publications = [
            [
                'title' => 'Laporan Tahunan Pembangunan Daerah 2024',
                'description' => 'Laporan komprehensif mengenai progress pembangunan daerah tahun 2024 yang mencakup berbagai sektor pembangunan infrastruktur, ekonomi, dan sosial.',
                'file_name' => 'laporan-tahunan-2024.pdf',
                'file_path' => 'dokumen_files/laporan-tahunan-2024.pdf',
                'file_type' => 'pdf',
                'file_size' => 2548736, // ~2.5MB
                'category' => 'Laporan',
                'download_count' => rand(15, 50),
                'created_at' => Carbon::now()->subDays(30),
                'updated_at' => Carbon::now()->subDays(30),
            ],
            [
                'title' => 'Panduan Pelayanan Publik Digital',
                'description' => 'Panduan lengkap untuk masyarakat dalam mengakses dan menggunakan layanan publik berbasis digital yang telah disediakan oleh pemerintah daerah.',
                'file_name' => 'panduan-pelayanan-digital.pdf',
                'file_path' => 'dokumen_files/panduan-pelayanan-digital.pdf',
                'file_type' => 'pdf',
                'file_size' => 1876543, // ~1.8MB
                'category' => 'Panduan',
                'download_count' => rand(25, 60),
                'created_at' => Carbon::now()->subDays(20),
                'updated_at' => Carbon::now()->subDays(20),
            ],
            [
                'title' => 'Policy Brief: Strategi Pengembangan UMKM',
                'description' => 'Dokumen kebijakan yang berisi strategi dan rekomendasi untuk pengembangan Usaha Mikro, Kecil, dan Menengah (UMKM) di era digital.',
                'file_name' => 'policy-brief-umkm.pdf',
                'file_path' => 'dokumen_files/policy-brief-umkm.pdf',
                'file_type' => 'pdf',
                'file_size' => 1234567, // ~1.2MB
                'category' => 'Policy Brief',
                'download_count' => rand(20, 40),
                'created_at' => Carbon::now()->subDays(15),
                'updated_at' => Carbon::now()->subDays(15),
            ],
            [
                'title' => 'Jurnal Penelitian Pembangunan Berkelanjutan',
                'description' => 'Kumpulan artikel penelitian tentang pembangunan berkelanjutan yang fokus pada aspek lingkungan, sosial, dan ekonomi di tingkat daerah.',
                'file_name' => 'jurnal-pembangunan-berkelanjutan.pdf',
                'file_path' => 'dokumen_files/jurnal-pembangunan-berkelanjutan.pdf',
                'file_type' => 'pdf',
                'file_size' => 3456789, // ~3.3MB
                'category' => 'Jurnal',
                'download_count' => rand(10, 35),
                'created_at' => Carbon::now()->subDays(10),
                'updated_at' => Carbon::now()->subDays(10),
            ],
        ];

        foreach ($publications as $publicationData) {
            // Create dummy file content
            $dummyContent = $this->generateDummyPdfContent($publicationData['title']);

            // Save dummy file to storage
            Storage::disk('public')->put($publicationData['file_path'], $dummyContent);

            // Create publication record
            Publication::create($publicationData);
        }

        $this->command->info('Publications seeded successfully!');
    }

    /**
     * Generate dummy PDF content (just text for demo purposes)
     */
    private function generateDummyPdfContent($title): string
    {
        return "PDF Content for: {$title}\n\n" .
            "This is a dummy PDF file created for seeding purposes.\n" .
            "In a real application, this would be an actual PDF file.\n\n" .
            "Generated at: " . Carbon::now()->format('Y-m-d H:i:s') . "\n" .
            str_repeat("Lorem ipsum dolor sit amet, consectetur adipiscing elit. ", 100);
    }
}
