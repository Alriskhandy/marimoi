<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProjectFeedback;

class ProjectFeedbackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $feedbacks = [
            [
                'nama_proyek' => 'Sistem Informasi Pelayanan Publik Maluku Utara',
                'nama_pemberi_aspirasi' => 'Ahmad Bahar',
                'kabupaten_kota' => 'Ternate',
                'kecamatan' => 'Ternate Tengah',
                'latitude' => 0.7881,
                'longitude' => 127.3781,
                'laporan_gambar' => 'screenshot_pelayanan_ternate.jpg',
                'tanggapan' => 'Setelah migrasi ke Laravel 10, loading website pelayanan publik menjadi lebih cepat. Namun masih ada kendala pada fitur upload dokumen KTP yang sering timeout.',
                'jenis_tanggapan' => 'keluhan',
                'status' => 'pending',
                'email' => 'ahmad.bahar@ternate.go.id',
                'phone' => '082188001234'
            ],
            [
                'nama_proyek' => 'Portal E-Commerce UMKM Maluku Utara',
                'nama_pemberi_aspirasi' => 'Siti Halima Tutuarima',
                'kabupaten_kota' => 'Tidore Kepulauan',
                'kecamatan' => 'Tidore',
                'latitude' => 0.6781,
                'longitude' => 127.4020,
                'laporan_gambar' => 'dashboard_umkm_tidore.jpg',
                'tanggapan' => 'Tampilan dashboard UMKM lebih modern dan mudah digunakan. Fitur katalog produk lokal seperti pala dan cengkeh jadi lebih menarik untuk pembeli dari luar daerah.',
                'jenis_tanggapan' => 'apresiasi',
                'status' => 'ditinjau',
                'email' => 'halima.umkm@tidorekepulauan.go.id',
                'phone' => '082188002345'
            ],
            [
                'nama_proyek' => 'Aplikasi Presensi ASN Halmahera Barat',
                'nama_pemberi_aspirasi' => 'Budi Sangadji',
                'kabupaten_kota' => 'Halmahera Barat',
                'kecamatan' => 'Jailolo',
                'latitude' => 1.0147,
                'longitude' => 127.7334,
                'laporan_gambar' => 'presensi_jailolo.jpg',
                'tanggapan' => 'Aplikasi presensi berjalan baik setelah migrasi. Namun untuk wilayah terpencil seperti desa-desa di Halmahera masih sulit akses internet, apakah bisa dibuat mode offline?',
                'jenis_tanggapan' => 'saran',
                'status' => 'pending',
                'email' => 'budi.sangadji@halmaherabarat.go.id',
                'phone' => '082188003456'
            ],
            [
                'nama_proyek' => 'Sistem Informasi Pendidikan Halmahera Timur',
                'nama_pemberi_aspirasi' => 'Dr. Maya Wattimena',
                'kabupaten_kota' => 'Halmahera Timur',
                'kecamatan' => 'Maba',
                'latitude' => 1.4853,
                'longitude' => 127.8492,
                'laporan_gambar' => 'sistem_pendidikan_maba.jpg',
                'tanggapan' => 'Sistem informasi sekolah sangat membantu monitoring siswa di wilayah kepulauan. Data kehadiran dan nilai siswa dari pulau-pulau terpencil bisa terpantau real-time.',
                'jenis_tanggapan' => 'apresiasi',
                'status' => 'selesai',
                'email' => 'maya.wattimena@halmaheratimur.go.id',
                'phone' => '082188004567',
                'response_admin' => 'Terima kasih atas feedback positifnya. Kami akan terus mengembangkan sistem pendidikan untuk daerah kepulauan.',
                'responded_at' => now()->subDays(2)
            ],
            [
                'nama_proyek' => 'Platform Booking Kapal Antar Pulau',
                'nama_pemberi_aspirasi' => 'Andi Papilaya',
                'kabupaten_kota' => 'Kepulauan Sula',
                'kecamatan' => 'Sanana',
                'latitude' => -1.9833,
                'longitude' => 125.9667,
                'laporan_gambar' => 'booking_kapal_sula.jpg',
                'tanggapan' => 'Fitur booking kapal sangat membantu masyarakat Sula untuk reservasi transportasi antar pulau. Bisa ditambahkan info cuaca dan gelombang laut tidak?',
                'jenis_tanggapan' => 'pertanyaan',
                'status' => 'ditinjau',
                'email' => 'andi.papilaya@kepulauansula.go.id',
                'phone' => '082188005678'
            ],
            [
                'nama_proyek' => 'Sistem Monitoring Hasil Laut Halmahera Selatan',
                'nama_pemberi_aspirasi' => 'Fatima Leiwakabessy',
                'kabupaten_kota' => 'Halmahera Selatan',
                'kecamatan' => 'Labuha',
                'latitude' => -0.9500,
                'longitude' => 127.4833,
                'laporan_gambar' => 'monitoring_laut_labuha.jpg',
                'tanggapan' => 'Aplikasi monitoring hasil tangkapan ikan dan budidaya rumput laut bekerja dengan baik. Sangat membantu nelayan dalam pelaporan hasil ke Dinas Kelautan.',
                'jenis_tanggapan' => 'apresiasi',
                'status' => 'pending',
                'email' => 'fatima.kelautan@halmaheraselatan.go.id',
                'phone' => '082188006789'
            ],
            [
                'nama_proyek' => 'Portal Wisata Bahari Maluku Utara',
                'nama_pemberi_aspirasi' => 'Rahman Loloda',
                'kabupaten_kota' => 'Halmahera Utara',
                'kecamatan' => 'Tobelo',
                'latitude' => 1.7281,
                'longitude' => 128.0139,
                'laporan_gambar' => 'portal_wisata_tobelo.jpg',
                'tanggapan' => 'Portal wisata bahari sangat bagus untuk promosi destinasi diving dan snorkeling di Maluku Utara. Content management system-nya mudah digunakan untuk update konten.',
                'jenis_tanggapan' => 'apresiasi',
                'status' => 'selesai',
                'email' => 'rahman.wisata@halmaherautara.go.id',
                'phone' => '082188007890',
                'response_admin' => 'Senang mendengar portal wisata bahari membantu promosi pariwisata daerah. Kami akan terus update fitur untuk mendukung sektor pariwisata.',
                'responded_at' => now()->subDays(1)
            ],
            [
                'nama_proyek' => 'Aplikasi Perizinan Tambang Maluku Utara',
                'nama_pemberi_aspirasi' => 'Dewi Soleman',
                'kabupaten_kota' => 'Pulau Morotai',
                'kecamatan' => 'Morotai Selatan',
                'latitude' => 2.3167,
                'longitude' => 128.4167,
                'laporan_gambar' => 'perizinan_tambang_morotai.jpg',
                'tanggapan' => 'Sistem perizinan tambang sudah lebih transparan dan trackable. Proses verifikasi dokumen lingkungan juga lebih cepat setelah digitalisasi.',
                'jenis_tanggapan' => 'apresiasi',
                'status' => 'ditindaklanjuti',
                'email' => 'dewi.soleman@pulaumorotai.go.id',
                'phone' => '082188008901'
            ],
            [
                'nama_proyek' => 'Sistem Keuangan Daerah Halmahera Tengah',
                'nama_pemberi_aspirasi' => 'Ir. Ruslan Taher',
                'kabupaten_kota' => 'Halmahera Tengah',
                'kecamatan' => 'Weda',
                'latitude' => -0.2167,
                'longitude' => 127.8833,
                'laporan_gambar' => 'keuangan_weda.jpg',
                'tanggapan' => 'Sistem keuangan daerah lebih terintegrasi dan transparan. Laporan APBD bisa diakses real-time oleh masyarakat. Namun perlu pelatihan untuk operator tingkat desa.',
                'jenis_tanggapan' => 'saran',
                'status' => 'ditindaklanjuti',
                'email' => 'ruslan.taher@halmaheratengah.go.id',
                'phone' => '082188009012'
            ],
            [
                'nama_proyek' => 'Portal Layanan Masyarakat Pulau Taliabu',
                'nama_pemberi_aspirasi' => 'Hj. Aminah Salampessy',
                'kabupaten_kota' => 'Pulau Taliabu',
                'kecamatan' => 'Taliabu Timur',
                'latitude' => -1.8333,
                'longitude' => 124.7833,
                'laporan_gambar' => 'layanan_taliabu.jpg',
                'tanggapan' => 'Portal layanan masyarakat sangat membantu warga Taliabu yang jauh dari ibukota kabupaten. Fitur pengajuan surat online menghemat biaya dan waktu perjalanan.',
                'jenis_tanggapan' => 'apresiasi',
                'status' => 'selesai',
                'email' => 'aminah.salampessy@pulautaliabu.go.id',
                'phone' => '082188010123',
                'response_admin' => 'Senang portal layanan membantu masyarakat di daerah terpencil. Akan kami perluas fitur-fitur layanan digital.',
                'responded_at' => now()->subDays(3)
            ]
        ];

        foreach ($feedbacks as $feedback) {
            ProjectFeedback::create($feedback);
        }
    }
}