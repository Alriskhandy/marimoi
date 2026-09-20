<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DataSpatial;
use App\Models\Opd;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendGeojsonMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_geojson_endpoint_exposes_dataset_metadata(): void
    {
        $category = Category::create(['type' => 'tematik', 'nama' => 'Fasilitas Uji', 'warna' => '#0d6efd']);
        $user = User::factory()->create();
        $opd = Opd::create(['name' => 'Dinas Uji Coba', 'singkatan' => 'DUC']);
        $data = DataSpatial::factory()->create([
            'user_id' => $user->id,
            'kategori_id' => $category->id,
            'sumber_data' => 'Survei lapangan 2025',
            'opd_pengelola_id' => $opd->id,
            'tanggal_data' => '2025-06-01',
        ]);

        $response = $this->getJson('/geojson?type=tematik');

        $response->assertOk();
        $feature = collect($response->json('features'))
            ->firstWhere('properties.uuid', $data->uuid);

        $this->assertNotNull($feature, 'feature untuk data uji tidak ditemukan pada response geojson');
        $this->assertSame('Survei lapangan 2025', $feature['properties']['sumber_data']);
        $this->assertSame('Dinas Uji Coba', $feature['properties']['opd_pengelola']);
        $this->assertSame('01-06-2025', $feature['properties']['tanggal_data']);
    }

    public function test_public_geojson_endpoint_still_returns_features_without_opd_pengelola(): void
    {
        $category = Category::create(['type' => 'tematik', 'nama' => 'Fasilitas Tanpa OPD', 'warna' => '#ff0000']);
        $user = User::factory()->create();
        $data = DataSpatial::factory()->create([
            'user_id' => $user->id,
            'kategori_id' => $category->id,
            'opd_pengelola_id' => null,
        ]);

        $response = $this->getJson('/geojson?type=tematik');

        $response->assertOk();
        $feature = collect($response->json('features'))
            ->firstWhere('properties.uuid', $data->uuid);

        $this->assertNotNull($feature, 'left join ke opd tidak boleh menghilangkan baris tanpa opd_pengelola_id');
        $this->assertNull($feature['properties']['opd_pengelola']);
    }
}
