<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportAdministrativeRegions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marimoi:import-wilayah {path : Path file GeoJSON batas wilayah} {--tingkat=kabupaten_kota}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import batas wilayah administratif dari GeoJSON ke administrative_regions';

    /**
     * Execute the console command.
     *
     * Sengaja belum diimplementasikan — sumber data resmi batas wilayah (shapefile/GeoJSON
     * BPS/Kemendagri) belum ditentukan (lihat Keputusan #3 di
     * docs/marimoi v2/04_implementation/09-implementasi-penuh-database-v2.md). Command ini
     * di-scaffold sekarang supaya begitu sumber data disepakati, implementasinya tinggal
     * mengisi method ini — tidak perlu membuat file baru lagi.
     */
    public function handle(): int
    {
        $this->error('Belum diimplementasikan — menunggu keputusan sumber data resmi batas wilayah (Keputusan #3).');

        return self::FAILURE;
    }
}
