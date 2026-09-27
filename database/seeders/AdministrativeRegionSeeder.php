<?php

namespace Database\Seeders;

use App\Models\AdministrativeRegion;
use Illuminate\Database\Seeder;

/**
 * Sumber: docs/marimoi v2/Kode_Wilayah_Provinsi_Maluku_Utara.md — rekapitulasi kode
 * wilayah kerja statistik (BPS) dan kode wilayah administrasi (Kemendagri). Nama
 * kecamatan memakai versi Kemendagri sebagai `name` utama (sesuai desain kolom
 * `administrative_regions` — "kode wilayah resmi Kemendagri"); beberapa kecamatan
 * punya nama berbeda di BPS (dicatat di komentar tiap baris yang berbeda).
 */
class AdministrativeRegionSeeder extends Seeder
{
    public function run(): void
    {
        $provinsi = AdministrativeRegion::updateOrCreate(
            ['code_kemendagri' => '82'],
            ['code_bps' => '82', 'name' => 'Maluku Utara', 'level' => 'provinsi', 'parent_id' => null]
        );

        $kabupatenData = [
            ['code_bps' => '8201', 'code_kemendagri' => '82.01', 'name' => 'Kabupaten Halmahera Barat', 'kecamatan' => [
                ['8201090', '82.01.01', 'Jailolo'],
                ['8201091', '82.01.05', 'Jailolo Selatan'],
                ['8201100', '82.01.04', 'Sahu'],
                ['8201101', '82.01.09', 'Sahu Timur'],
                ['8201130', '82.01.03', 'Ibu'],
                ['8201131', '82.01.08', 'Ibu Selatan'],
                ['8201132', '82.01.07', 'Ibu Utara'], // BPS: Tabaru
                ['8201140', '82.01.02', 'Loloda'],
                ['8201141', '82.01.10', 'Loloda Tengah'],
            ]],
            ['code_bps' => '8202', 'code_kemendagri' => '82.02', 'name' => 'Kabupaten Halmahera Tengah', 'kecamatan' => [
                ['8202030', '82.02.01', 'Weda'],
                ['8202031', '82.02.05', 'Weda Selatan'],
                ['8202032', '82.02.04', 'Weda Utara'],
                ['8202033', '82.02.07', 'Weda Tengah'],
                ['8202034', '82.02.09', 'Weda Timur'],
                ['8202041', '82.02.03', 'Pulau Gebe'],
                ['8202042', '82.02.02', 'Patani'],
                ['8202043', '82.02.06', 'Patani Utara'],
                ['8202044', '82.02.08', 'Patani Barat'],
                ['8202045', '82.02.10', 'Patani Timur'],
            ]],
            ['code_bps' => '8205', 'code_kemendagri' => '82.03', 'name' => 'Kabupaten Halmahera Utara', 'kecamatan' => [
                ['8205010', '82.03.08', 'Malifut'],
                ['8205011', '82.03.22', 'Kao Teluk'],
                ['8205020', '82.03.07', 'Kao'],
                ['8205021', '82.03.21', 'Kao Barat'],
                ['8205022', '82.03.20', 'Kao Utara'],
                ['8205030', '82.03.06', 'Tobelo Selatan'],
                ['8205031', '82.03.13', 'Tobelo Barat'],
                ['8205032', '82.03.12', 'Tobelo Timur'],
                ['8205040', '82.03.05', 'Tobelo'],
                ['8205041', '82.03.11', 'Tobelo Tengah'],
                ['8205042', '82.03.10', 'Tobelo Utara'],
                ['8205050', '82.03.04', 'Galela'],
                ['8205051', '82.03.16', 'Galela Selatan'],
                ['8205052', '82.03.14', 'Galela Barat'],
                ['8205053', '82.03.15', 'Galela Utara'],
                ['8205060', '82.03.09', 'Loloda Utara'],
                ['8205061', '82.03.19', 'Loloda Kepulauan'],
            ]],
            ['code_bps' => '8204', 'code_kemendagri' => '82.04', 'name' => 'Kabupaten Halmahera Selatan', 'kecamatan' => [
                ['8204010', '82.04.05', 'Obi Selatan'],
                ['8204020', '82.04.06', 'Obi'],
                ['8204021', '82.04.28', 'Obi Barat'],
                ['8204022', '82.04.29', 'Obi Timur'],
                ['8204023', '82.04.30', 'Obi Utara'],
                ['8204030', '82.04.08', 'Bacan'],
                ['8204031', '82.04.19', 'Mandioli Selatan'],
                ['8204032', '82.04.20', 'Mandioli Utara'],
                ['8204033', '82.04.17', 'Bacan Selatan'],
                ['8204034', '82.04.18', 'Kepulauan Botanglomang'], // BPS: Batang Lomang
                ['8204040', '82.04.07', 'Bacan Timur'],
                ['8204041', '82.04.21', 'Bacan Timur Selatan'],
                ['8204042', '82.04.22', 'Bacan Timur Tengah'],
                ['8204050', '82.04.09', 'Bacan Barat'],
                ['8204051', '82.04.15', 'Kasiruta Barat'],
                ['8204052', '82.04.16', 'Kasiruta Timur'],
                ['8204053', '82.04.14', 'Bacan Barat Utara'],
                ['8204060', '82.04.02', 'Kayoa'],
                ['8204061', '82.04.11', 'Kayoa Barat'],
                ['8204062', '82.04.12', 'Kayoa Selatan'],
                ['8204063', '82.04.13', 'Kayoa Utara'],
                ['8204070', '82.04.01', 'Pulau Makian'],
                ['8204071', '82.04.10', 'Makian Barat'],
                ['8204080', '82.04.04', 'Gane Barat'],
                ['8204081', '82.04.23', 'Gane Barat Selatan'],
                ['8204082', '82.04.24', 'Gane Barat Utara'],
                ['8204083', '82.04.25', 'Kepulauan Joronga'],
                ['8204090', '82.04.03', 'Gane Timur'],
                ['8204091', '82.04.27', 'Gane Timur Tengah'],
                ['8204092', '82.04.26', 'Gane Timur Selatan'],
            ]],
            ['code_bps' => '8203', 'code_kemendagri' => '82.05', 'name' => 'Kabupaten Kepulauan Sula', 'kecamatan' => [
                ['8203010', '82.05.03', 'Sulabesi Barat'], // BPS: Sula Besi Barat
                ['8203011', '82.05.09', 'Sulabesi Selatan'],
                ['8203020', '82.05.02', 'Sanana'],
                ['8203021', '82.05.07', 'Sulabesi Tengah'], // BPS: Sula Besi Tengah
                ['8203022', '82.05.08', 'Sulabesi Timur'],
                ['8203023', '82.05.18', 'Sanana Utara'],
                ['8203030', '82.05.01', 'Mangoli Timur'],
                ['8203031', '82.05.11', 'Mangoli Tengah'],
                ['8203032', '82.05.10', 'Mangoli Utara Timur'],
                ['8203040', '82.05.06', 'Mangoli Barat'],
                ['8203041', '82.05.13', 'Mangoli Utara'],
                ['8203042', '82.05.12', 'Mangoli Selatan'],
            ]],
            ['code_bps' => '8206', 'code_kemendagri' => '82.06', 'name' => 'Kabupaten Halmahera Timur', 'kecamatan' => [
                ['8206010', '82.06.03', 'Maba Selatan'],
                ['8206011', '82.06.10', 'Kota Maba'],
                ['8206020', '82.06.04', 'Wasile Selatan'],
                ['8206030', '82.06.01', 'Wasile'],
                ['8206031', '82.06.07', 'Wasile Timur'],
                ['8206032', '82.06.05', 'Wasile Tengah'],
                ['8206033', '82.06.06', 'Wasile Utara'],
                ['8206040', '82.06.02', 'Maba'],
                ['8206041', '82.06.08', 'Maba Tengah'],
                ['8206042', '82.06.09', 'Maba Utara'],
            ]],
            ['code_bps' => '8207', 'code_kemendagri' => '82.07', 'name' => 'Kabupaten Pulau Morotai', 'kecamatan' => [
                ['8207010', '82.07.01', 'Morotai Selatan'],
                ['8207020', '82.07.05', 'Morotai Timur'],
                ['8207030', '82.07.02', 'Morotai Selatan Barat'],
                ['8207031', '82.07.06', 'Pulau Rao'],
                ['8207040', '82.07.03', 'Morotai Jaya'],
                ['8207050', '82.07.04', 'Morotai Utara'],
            ]],
            ['code_bps' => '8208', 'code_kemendagri' => '82.08', 'name' => 'Kabupaten Pulau Taliabu', 'kecamatan' => [
                ['8208010', '82.08.01', 'Taliabu Barat'],
                ['8208020', '82.08.07', 'Taliabu Selatan'],
                ['8208030', '82.08.08', 'Tabona'],
                ['8208040', '82.08.06', 'Taliabu Timur Selatan'],
                ['8208050', '82.08.05', 'Taliabu Timur'],
                ['8208060', '82.08.04', 'Taliabu Utara'],
                ['8208070', '82.08.03', 'Lede'],
                ['8208080', '82.08.02', 'Taliabu Barat Laut'],
            ]],
            ['code_bps' => '8271', 'code_kemendagri' => '82.71', 'name' => 'Kota Ternate', 'kecamatan' => [
                ['8271010', '82.71.01', 'Pulau Ternate'],
                ['8271011', '82.71.04', 'Moti'],
                ['8271012', '82.71.05', 'Pulau Batang Dua'],
                ['8271013', '82.71.07', 'Pulau Hiri'],
                ['8271014', '82.71.08', 'Ternate Barat'],
                ['8271020', '82.71.02', 'Kota Ternate Selatan'], // BPS: Ternate Selatan
                ['8271021', '82.71.06', 'Kota Ternate Tengah'], // BPS: Ternate Tengah
                ['8271030', '82.71.03', 'Kota Ternate Utara'], // BPS: Ternate Utara
            ]],
            ['code_bps' => '8272', 'code_kemendagri' => '82.72', 'name' => 'Kota Tidore Kepulauan', 'kecamatan' => [
                ['8272010', '82.72.04', 'Tidore Selatan'],
                ['8272020', '82.72.05', 'Tidore Utara'],
                ['8272030', '82.72.01', 'Tidore'],
                ['8272031', '82.72.08', 'Tidore Timur'],
                ['8272040', '82.72.03', 'Oba'],
                ['8272041', '82.72.07', 'Oba Selatan'],
                ['8272050', '82.72.02', 'Oba Utara'],
                ['8272051', '82.72.06', 'Oba Tengah'],
            ]],
        ];

        foreach ($kabupatenData as $kab) {
            $kabupaten = AdministrativeRegion::updateOrCreate(
                ['code_kemendagri' => $kab['code_kemendagri']],
                [
                    'code_bps' => $kab['code_bps'],
                    'name' => $kab['name'],
                    'level' => 'kabupaten_kota',
                    'parent_id' => $provinsi->id,
                ]
            );

            foreach ($kab['kecamatan'] as [$codeBps, $codeKemendagri, $nama]) {
                AdministrativeRegion::updateOrCreate(
                    ['code_kemendagri' => $codeKemendagri],
                    [
                        'code_bps' => $codeBps,
                        'name' => $nama,
                        'level' => 'kecamatan',
                        'parent_id' => $kabupaten->id,
                    ]
                );
            }
        }
    }
}
