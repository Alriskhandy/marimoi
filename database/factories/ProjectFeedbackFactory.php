<?php

namespace Database\Factories;

use App\Models\ProjectFeedback;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFeedbackFactory extends Factory
{
    protected $model = ProjectFeedback::class;

    public function definition(): array
    {
        $projects = [
            'Sistem Informasi Pelayanan Publik',
            'Portal E-Commerce UMKM',
            'Aplikasi Presensi ASN',
            'Sistem Informasi Pendidikan',
            'Platform Booking Kapal Antar Pulau',
            'Sistem Monitoring Hasil Laut',
            'Portal Wisata Bahari',
            'Aplikasi Perizinan Tambang',
            'Sistem Keuangan Daerah',
            'Portal Layanan Masyarakat Kepulauan'
        ];

        $kabupatenKota = [
            'Ternate', 'Tidore Kepulauan', 'Halmahera Barat', 'Halmahera Timur',
            'Halmahera Utara', 'Halmahera Selatan', 'Kepulauan Sula', 'Halmahera Tengah',
            'Pulau Morotai', 'Pulau Taliabu'
        ];

        $kecamatanByKabupaten = [
            'Ternate' => ['Ternate Tengah', 'Ternate Utara', 'Ternate Selatan', 'Pulau Ternate'],
            'Tidore Kepulauan' => ['Tidore', 'Tidore Timur', 'Tidore Utara', 'Oba'],
            'Halmahera Barat' => ['Jailolo', 'Jailolo Selatan', 'Sahu', 'Ibu'],
            'Halmahera Timur' => ['Maba', 'Maba Selatan', 'Wasile', 'Wasile Timur'],
            'Halmahera Utara' => ['Tobelo', 'Tobelo Barat', 'Tobelo Timur', 'Galela'],
            'Halmahera Selatan' => ['Labuha', 'Bacan', 'Bacan Timur', 'Makian'],
            'Kepulauan Sula' => ['Sanana', 'Mangole', 'Taliabu', 'Sulabesi'],
            'Halmahera Tengah' => ['Weda', 'Patani', 'Patani Barat', 'Gane Barat'],
            'Pulau Morotai' => ['Morotai Selatan', 'Morotai Timur', 'Morotai Utara', 'Morotai Jaya'],
            'Pulau Taliabu' => ['Taliabu Timur', 'Taliabu Barat', 'Taliabu Utara', 'Taliabu Selatan']
        ];

        $tanggapanTemplates = [
            'Setelah migrasi ke Laravel 10, sistem berjalan lebih stabil untuk wilayah kepulauan.',
            'Interface baru lebih mudah digunakan oleh masyarakat di daerah terpencil.',
            'Masih ada kendala koneksi internet di beberapa pulau kecil.',
            'Fitur offline sangat membantu saat akses internet terbatas.',
            'Loading aplikasi lebih cepat meski dengan bandwidth terbatas.',
            'Perlu sosialisasi tambahan untuk masyarakat kepulauan tentang fitur baru.',
            'Sistem backup data bekerja baik meski ada gangguan listrik sering.',
            'Fitur mobile sangat cocok untuk kondisi geografis Maluku Utara.',
            'Integrasi dengan sistem lama perlu penyesuaian untuk kondisi daerah.',
            'Sangat membantu pelayanan publik di wilayah kepulauan terpencil.'
        ];

        $projects = [
            'Sistem Informasi Pelayanan Publik',
            'Portal E-Commerce UMKM',
            'Aplikasi Presensi ASN',
            'Sistem Informasi Pendidikan',
            'Platform Booking Kapal Antar Pulau',
            'Sistem Monitoring Hasil Laut',
            'Portal Wisata Bahari',
            'Aplikasi Perizinan Tambang',
            'Sistem Keuangan Daerah',
            'Portal Layanan Masyarakat Kepulauan'
        ];

        $selectedKabupaten = $this->faker->randomElement(ProjectFeedback::KABUPATEN_KOTA);
        $selectedKecamatan = $this->faker->randomElement(ProjectFeedback::KECAMATAN_BY_KABUPATEN[$selectedKabupaten]);
        
        // Koordinat realistis untuk Maluku Utara
        $coordinates = $this->getMalukuUtaraCoordinates($selectedKabupaten);
        
        $status = $this->faker->randomElement(['pending', 'ditinjau', 'ditindaklanjuti', 'selesai']);
        $responded = $status === 'selesai' ? $this->faker->boolean(80) : false;

        return [
            'nama_proyek' => $this->faker->randomElement($projects) . ' ' . $selectedKabupaten,
            'nama_pemberi_aspirasi' => $this->faker->name(),
            'kabupaten_kota' => $selectedKabupaten,
            'kecamatan' => $selectedKecamatan,
            'latitude' => $coordinates['lat'] + $this->faker->randomFloat(4, -0.1, 0.1),
            'longitude' => $coordinates['lng'] + $this->faker->randomFloat(4, -0.1, 0.1),
            'laporan_gambar' => $this->faker->boolean(70) ? 'screenshot_' . strtolower(str_replace(' ', '_', $selectedKabupaten)) . '_' . $this->faker->uuid() . '.jpg' : null,
            'tanggapan' => $this->faker->randomElement($tanggapanTemplates) . ' ' . $this->faker->sentence(),
            'jenis_tanggapan' => $this->faker->randomElement(['keluhan', 'saran', 'apresiasi', 'pertanyaan']),
            'status' => $status,
            'email' => $this->faker->firstName() . '.' . $this->faker->lastName() . '@' . strtolower(str_replace(' ', '', $selectedKabupaten)) . '.go.id',
            'phone' => '0821' . $this->faker->randomNumber(8, true),
            'response_admin' => $responded ? $this->faker->sentence() : null,
            'responded_at' => $responded ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
            'created_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
        ];
    }
    public function pending()
    {
        return $this->state([
            'status' => 'pending',
            'response_admin' => null,
            'responded_at' => null,
        ]);
    }

    public function ditinjau()
    {
        return $this->state([
            'status' => 'ditinjau',
            'response_admin' => null,
            'responded_at' => null,
        ]);
    }

    public function ditindaklanjuti()
    {
        return $this->state([
            'status' => 'ditindaklanjuti',
            'response_admin' => $this->faker->sentence(),
            'responded_at' => $this->faker->dateTimeBetween('-2 weeks', 'now'),
        ]);
    }

    public function selesai()
    {
        return $this->state([
            'status' => 'selesai',
            'response_admin' => $this->faker->sentence(),
            'responded_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    public function keluhan()
    {
        return $this->state([
            'jenis_tanggapan' => 'keluhan',
            'tanggapan' => 'Masih ada kendala dalam sistem setelah migrasi. ' . $this->faker->sentence(),
        ]);
    }

    public function saran()
    {
        return $this->state([
            'jenis_tanggapan' => 'saran',
            'tanggapan' => 'Sebaiknya ditambahkan fitur yang lebih sesuai untuk daerah kepulauan. ' . $this->faker->sentence(),
        ]);
    }

    public function apresiasi()
    {
        return $this->state([
            'jenis_tanggapan' => 'apresiasi',
            'tanggapan' => 'Sistem berjalan dengan baik dan sangat membantu pelayanan masyarakat. ' . $this->faker->sentence(),
        ]);
    }

    public function pertanyaan()
    {
        return $this->state([
            'jenis_tanggapan' => 'pertanyaan',
            'tanggapan' => 'Apakah ada rencana pengembangan fitur tambahan? ' . $this->faker->sentence(),
        ]);
    }

    public function forKabupaten($kabupaten)
    {
        if (!in_array($kabupaten, ProjectFeedback::KABUPATEN_KOTA)) {
            throw new \InvalidArgumentException("Kabupaten '$kabupaten' tidak valid untuk Maluku Utara");
        }

        $coordinates = $this->getMalukuUtaraCoordinates($kabupaten);
        $selectedKecamatan = $this->faker->randomElement(ProjectFeedback::KECAMATAN_BY_KABUPATEN[$kabupaten]);

        return $this->state([
            'kabupaten_kota' => $kabupaten,
            'kecamatan' => $selectedKecamatan,
            'latitude' => $coordinates['lat'] + $this->faker->randomFloat(4, -0.05, 0.05),
            'longitude' => $coordinates['lng'] + $this->faker->randomFloat(4, -0.05, 0.05),
            'email' => $this->faker->firstName() . '.' . $this->faker->lastName() . '@' . strtolower(str_replace(' ', '', $kabupaten)) . '.go.id',
        ]);
    }

    public function withImage()
    {
        return $this->state([
            'laporan_gambar' => 'screenshot_' . $this->faker->uuid() . '.jpg',
        ]);
    }

    public function withoutImage()
    {
        return $this->state([
            'laporan_gambar' => null,
        ]);
    }

    public function recent()
    {
        return $this->state([
            'created_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    public function old()
    {
        return $this->state([
            'created_at' => $this->faker->dateTimeBetween('-6 months', '-3 months'),
        ]);
    }

    private function getMalukuUtaraCoordinates($kabupaten)
    {
        $coordinates = [
            'Ternate' => ['lat' => 0.7881, 'lng' => 127.3781],
            'Tidore Kepulauan' => ['lat' => 0.6781, 'lng' => 127.4020],
            'Halmahera Barat' => ['lat' => 1.0147, 'lng' => 127.7334],
            'Halmahera Timur' => ['lat' => 1.4853, 'lng' => 127.8492],
            'Halmahera Utara' => ['lat' => 1.7281, 'lng' => 128.0139],
            'Halmahera Selatan' => ['lat' => -0.9500, 'lng' => 127.4833],
            'Kepulauan Sula' => ['lat' => -1.9833, 'lng' => 125.9667],
            'Halmahera Tengah' => ['lat' => -0.2167, 'lng' => 127.8833],
            'Pulau Morotai' => ['lat' => 2.3167, 'lng' => 128.4167],
            'Pulau Taliabu' => ['lat' => -1.8333, 'lng' => 124.7833]
        ];

        return $coordinates[$kabupaten] ?? ['lat' => 0.7881, 'lng' => 127.3781];
    }
           
}