<?php

namespace Tests\Feature;

use App\Models\AdministrativeRegion;
use Database\Seeders\AdministrativeRegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdministrativeRegionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_full_hierarchy_for_maluku_utara(): void
    {
        $this->seed(AdministrativeRegionSeeder::class);

        $this->assertSame(1, AdministrativeRegion::level('provinsi')->count());
        $this->assertSame(10, AdministrativeRegion::level('kabupaten_kota')->count());
        $this->assertSame(118, AdministrativeRegion::level('kecamatan')->count());
        $this->assertSame(129, AdministrativeRegion::count());
    }

    public function test_every_row_has_both_bps_and_kemendagri_codes(): void
    {
        $this->seed(AdministrativeRegionSeeder::class);

        $this->assertSame(0, AdministrativeRegion::whereNull('code_bps')->count());
        $this->assertSame(0, AdministrativeRegion::whereNull('code_kemendagri')->count());
    }

    public function test_kecamatan_are_linked_to_the_correct_kabupaten(): void
    {
        $this->seed(AdministrativeRegionSeeder::class);

        $halbar = AdministrativeRegion::where('code_kemendagri', '82.01')->firstOrFail();

        $this->assertSame(9, $halbar->children()->count());
        $this->assertTrue($halbar->children()->where('name', 'Jailolo')->exists());
    }

    public function test_provinsi_is_parent_of_all_kabupaten(): void
    {
        $this->seed(AdministrativeRegionSeeder::class);

        $provinsi = AdministrativeRegion::where('code_kemendagri', '82')->firstOrFail();

        $this->assertSame(10, $provinsi->children()->where('level', 'kabupaten_kota')->count());
    }

    public function test_name_differences_between_bps_and_kemendagri_are_preserved_via_bps_code(): void
    {
        $this->seed(AdministrativeRegionSeeder::class);

        // BPS menyebutnya "Tabaru", Kemendagri menyebutnya "Ibu Utara" — name pakai
        // versi Kemendagri, code_bps tetap menyimpan kode aslinya untuk ditelusuri.
        $region = AdministrativeRegion::where('code_bps', '8201132')->firstOrFail();

        $this->assertSame('Ibu Utara', $region->name);
        $this->assertSame('82.01.07', $region->code_kemendagri);
    }

    public function test_seeder_is_idempotent_when_run_twice(): void
    {
        $this->seed(AdministrativeRegionSeeder::class);
        $this->seed(AdministrativeRegionSeeder::class);

        $this->assertSame(129, AdministrativeRegion::count());
    }
}
