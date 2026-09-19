<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DataSpatial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HomepageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_spatial_storytelling_with_real_data(): void
    {
        $response = $this->get(route('beranda'));

        $response->assertOk();
        $response->assertSee('Memetakan Masa Depan', false);
        $response->assertSee('id="heroNodes"', false);
        $response->assertSee('id="homeMap"', false);
        $response->assertSee('data-parallax', false);
        $response->assertSee('id="flowCanvas"', false);
        $this->assertSame(6, substr_count($response->getContent(), 'data-panel data-on'));
        $response->assertSee('mockup/tab-mockup.webp', false);
        $response->assertSee('window.MARIMOI_HOME', false);
        $response->assertSee('Developed by', false);
        $response->assertDontSee('bootstrap', false);
        $response->assertDontSee('class="btn', false);
        $response->assertDontSee('class="container', false);

        foreach (['tampil.tematik', 'tampil.prioritas', 'tampil.aspirasi', 'tampil.publikasi'] as $name) {
            $response->assertSee('href="'.route($name).'"', false);
        }
    }

    public function test_homepage_points_only_include_valid_coordinates_within_maluku_utara(): void
    {
        Cache::forget('home.spatial-summary');

        $user = User::factory()->create();
        $category = Category::create(['type' => 'tematik', 'nama' => 'Puskesmas Uji', 'warna' => '#ff0000']);
        $make = fn (string $wkt) => DataSpatial::factory()->create([
            'user_id' => $user->id,
            'kategori_id' => $category->id,
            'geom' => DB::raw("ST_SetSRID(ST_GeomFromText('{$wkt}'), 4326)"),
        ]);

        $valid = $make('POINT(127.5 1.2)');
        $make('POINT(14422913 100000)');   // satuan meter (Mercator) berlabel derajat
        $make('POINT(0 0)');                // koordinat kosong
        $make('POINT(106.8 -6.2)');         // di luar Maluku Utara
        // Poligon bercampur dengan titik: ST_X tidak boleh dievaluasi pada poligon.
        $make('POLYGON((127 1, 128 1, 128 2, 127 1))');

        $points = $this->get(route('beranda'))->assertOk()->viewData('spatial')['points'];

        $this->assertCount(1, $points);
        $this->assertSame($valid->id, $points[0]['id']);
    }

    public function test_homepage_draws_the_region_outline_from_real_administrative_boundaries(): void
    {
        Cache::forget('home.spatial-summary');

        $user = User::factory()->create();
        $batas = Category::create(['type' => 'tematik', 'nama' => 'Batas Administrasi', 'warna' => '#111111']);
        $kota = Category::create(['type' => 'tematik', 'nama' => 'Kota Ternate', 'warna' => '#222222', 'parent_id' => $batas->id]);
        $lain = Category::create(['type' => 'tematik', 'nama' => 'Kawasan Lain', 'warna' => '#333333']);
        $poly = fn (int $categoryId, string $wkt) => DataSpatial::factory()->create([
            'user_id' => $user->id,
            'kategori_id' => $categoryId,
            'geom' => DB::raw("ST_SetSRID(ST_GeomFromText('{$wkt}'), 4326)"),
        ]);

        $poly($kota->id, 'MULTIPOLYGON(((127 0, 128 0, 128 1, 127 1, 127 0)))');
        $poly($kota->id, 'POLYGON((14422913 100000, 14423913 100000, 14423913 101000, 14422913 100000))'); // rusak (meter)
        $poly($lain->id, 'POLYGON((126 0, 127 0, 127 1, 126 0))');                                          // bukan batas administrasi

        $shapes = $this->get(route('beranda'))->assertOk()->viewData('spatial')['shapes'];

        $this->assertCount(1, $shapes);
        $this->assertEquals([127, 0], $shapes[0][0][0]);
    }
}
