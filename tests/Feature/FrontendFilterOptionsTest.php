<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DataSpatial;
use App\Models\Opd;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendFilterOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_filter_options_endpoint_returns_distinct_values_without_needing_any_layer_active(): void
    {
        $category = Category::create(['type' => 'tematik', 'nama' => 'Fasilitas Uji', 'warna' => '#0d6efd']);
        $user = User::factory()->create();
        $opd = Opd::create(['name' => 'Dinas Uji Coba', 'singkatan' => 'DUC']);

        DataSpatial::factory()->create([
            'user_id' => $user->id,
            'kategori_id' => $category->id,
            'tahun' => 2025,
            'opd_pengelola_id' => $opd->id,
            'dbf_attributes' => ['KABUPATEN' => 'Kota Ternate'],
        ]);
        DataSpatial::factory()->create([
            'user_id' => $user->id,
            'kategori_id' => $category->id,
            'tahun' => 2024,
            'opd_pengelola_id' => null,
            'dbf_attributes' => ['KABUPATEN' => 'Kota Ternate'],
        ]);

        $response = $this->getJson('/geojson/filter-options?type=tematik');

        $response->assertOk();
        $response->assertJson([
            'kabupaten' => ['Kota Ternate'],
            'opd_pengelola' => ['Dinas Uji Coba'],
        ]);
        $this->assertEqualsCanonicalizing([2024, 2025], $response->json('tahun'));
    }

    public function test_filter_categories_endpoint_returns_only_categories_matching_kabupaten(): void
    {
        $ternate = Category::create(['type' => 'tematik', 'nama' => 'Layer Ternate', 'warna' => '#0d6efd']);
        $tidore = Category::create(['type' => 'tematik', 'nama' => 'Layer Tidore', 'warna' => '#ff0000']);
        $user = User::factory()->create();

        DataSpatial::factory()->create([
            'user_id' => $user->id,
            'kategori_id' => $ternate->id,
            'dbf_attributes' => ['KABUPATEN' => 'Kota Ternate'],
        ]);
        DataSpatial::factory()->create([
            'user_id' => $user->id,
            'kategori_id' => $tidore->id,
            'dbf_attributes' => ['KABUPATEN' => 'Kota Tidore'],
        ]);

        $response = $this->getJson('/geojson/filter-categories?type=tematik&kabupaten='.urlencode('Kota Ternate'));

        $response->assertOk();
        $response->assertJson(['categories' => ['Layer Ternate']]);
    }

    public function test_filter_categories_endpoint_combines_kabupaten_tahun_and_opd_with_and_logic(): void
    {
        $category = Category::create(['type' => 'tematik', 'nama' => 'Layer Gabungan', 'warna' => '#0d6efd']);
        $user = User::factory()->create();
        $opd = Opd::create(['name' => 'Dinas Uji Coba', 'singkatan' => 'DUC']);

        DataSpatial::factory()->create([
            'user_id' => $user->id,
            'kategori_id' => $category->id,
            'tahun' => 2025,
            'opd_pengelola_id' => $opd->id,
            'dbf_attributes' => ['KABUPATEN' => 'Kota Ternate'],
        ]);

        $matching = $this->getJson(
            '/geojson/filter-categories?type=tematik&kabupaten='.urlencode('Kota Ternate').'&tahun=2025&opd_pengelola='.urlencode('Dinas Uji Coba')
        );
        $matching->assertOk()->assertJson(['categories' => ['Layer Gabungan']]);

        $notMatching = $this->getJson(
            '/geojson/filter-categories?type=tematik&kabupaten='.urlencode('Kota Ternate').'&tahun=2020'
        );
        $notMatching->assertOk()->assertJson(['categories' => []]);
    }
}
