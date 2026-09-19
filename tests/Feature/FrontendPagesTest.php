<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DataSpatial;
use App\Models\User;
use App\Support\MapDataVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FrontendPagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function publicPages(): array
    {
        return [
            'peta tematik' => ['tampil.tematik', false],
            'prioritas daerah' => ['tampil.prioritas', true],
            'publikasi' => ['tampil.publikasi', true],
            'aspirasi' => ['tampil.aspirasi', true],
            'profil reformer' => ['tampil.reformer', true],
            'kebijakan privasi' => ['kebijakan_privasi', true],
            'syarat ketentuan' => ['syarat_ketentuan', true],
            'tentang' => ['tampil.tentang', true],
            'faq' => ['tampil.faq', true],
        ];
    }

    #[DataProvider('publicPages')]
    public function test_public_page_uses_the_shared_spatial_shell(string $route, bool $hasFooter): void
    {
        $response = $this->get(route($route));

        $response->assertOk();
        $response->assertSee('id="nav"', false);
        $response->assertSee('id="mobileMenu"', false);
        $response->assertDontSee('class="navbar"', false);

        if ($hasFooter) {
            $response->assertSee('Developed by', false);
        } else {
            $response->assertDontSee('Developed by', false);
        }
    }

    public function test_content_pages_show_the_page_hero_with_breadcrumb(): void
    {
        $this->get(route('tampil.publikasi'))
            ->assertOk()
            ->assertSee('Dokumen Publikasi')
            ->assertSee('Breadcrumb', false);
    }

    public function test_about_and_faq_pages_show_their_content(): void
    {
        $this->get(route('tampil.tentang'))
            ->assertOk()
            ->assertSee('Filosofi logo')
            ->assertSee('Kenali MARIMOI dalam video')
            ->assertSee('data-video-id="rxI6vk7dFGw"', false)
            ->assertSee('Enam prinsip')
            ->assertSee('Dukungan Terhadap MARIMOI')
            ->assertSee('data-video-id="cWA8hBj4PcE"', false)
            ->assertSee('id="videoModal"', false);

        $this->get(route('tampil.faq'))
            ->assertOk()
            ->assertSee('Apa itu MARIMOI?')
            ->assertSee('Bagaimana cara menyampaikan aspirasi?');
    }

    public function test_reformer_profile_shows_structured_history(): void
    {
        $this->get(route('tampil.reformer'))
            ->assertOk()
            ->assertSee('Riwayat pendidikan')
            ->assertSee('Riwayat jabatan')
            ->assertSee('data-cv-open', false)
            ->assertDontSee('Mangga Dua');
    }

    public function test_thematic_map_page_keeps_map_controls_and_shows_hud(): void
    {
        $this->get(route('tampil.tematik'))
            ->assertOk()
            ->assertSee('id="map"', false)
            ->assertSee('id="map-hud"', false)
            ->assertSee('id="sidebar-layer"', false)
            ->assertSee('id="btn-toggle-sidebar-basemap"', false)
            ->assertSee('id="btn-share-map"', false);
    }

    public function test_detail_map_reprojects_legacy_web_mercator_geometry_to_lon_lat(): void
    {
        $category = Category::create(['type' => 'tematik', 'nama' => 'Kawasan Uji', 'warna' => '#0d6efd']);
        $user = User::factory()->create();

        // Poligon dalam meter (Web Mercator) yang tersimpan berlabel 4326, seperti data lama.
        $data = DataSpatial::factory()->create([
            'user_id' => $user->id,
            'kategori_id' => $category->id,
            'geom' => DB::raw("ST_SetSRID(ST_GeomFromText('MULTIPOLYGON(((14422913 100000, 14423913 100000, 14423913 101000, 14422913 100000)))'), 4326)"),
        ]);

        $response = $this->get(route('detail.tematik', $data->uuid));

        $response->assertOk();
        $geometry = $response->viewData('project')->geojson;
        $this->assertSame('MultiPolygon', $geometry->type);

        [$lon, $lat] = $geometry->coordinates[0][0][0];
        $this->assertEqualsWithDelta(129.56, $lon, 0.05);
        $this->assertEqualsWithDelta(0.9, $lat, 0.05);
    }

    public function test_detail_pages_render_the_shared_detail_layout_with_map_and_attributes(): void
    {
        $category = Category::create(['type' => 'tematik', 'nama' => 'Fasilitas Uji', 'warna' => '#ff0000']);
        $user = User::factory()->create();
        $data = DataSpatial::factory()->create([
            'user_id' => $user->id,
            'kategori_id' => $category->id,
            'dbf_attributes' => ['ANGGARAN' => 'Rp. 1.500.000.000', 'ID' => 5],
        ]);

        $this->get(route('detail.tematik', $data->uuid))
            ->assertOk()
            ->assertSee('id="map-detail"', false)
            ->assertSee('Fasilitas Uji')
            ->assertSee('ANGGARAN')
            ->assertSee('Rp. 1.500.000.000')
            ->assertDontSee('>ID<', false);
    }

    public function test_tematik_map_version_is_public_and_stable_until_data_or_category_changes(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['type' => 'tematik', 'nama' => 'Kawasan A', 'warna' => '#0d6efd']);
        $item = DataSpatial::factory()->create(['user_id' => $user->id, 'kategori_id' => $category->id]);

        $version = fn () => $this->getJson(route('tematik.version'))->assertOk()->json('version');

        $first = $version();
        $this->assertNotEmpty($first);
        $this->assertSame($first, $version(), 'versi stabil bila tidak ada perubahan');

        $this->travel(2)->seconds();
        $item->update(['deskripsi' => 'Diubah']);
        $afterUpdate = $version();
        $this->assertNotSame($first, $afterUpdate, 'ubah data mengubah versi');

        $this->travel(2)->seconds();
        $category->update(['nama' => 'Kawasan A (baru)']);
        $afterCategory = $version();
        $this->assertNotSame($afterUpdate, $afterCategory, 'ubah kategori mengubah versi');

        $this->travel(2)->seconds();
        DataSpatial::factory()->create(['user_id' => $user->id, 'kategori_id' => $category->id]);
        $afterAdd = $version();
        $this->assertNotSame($afterCategory, $afterAdd, 'tambah data mengubah versi');

        $item->delete();
        $this->assertNotSame($afterAdd, $version(), 'hapus data mengubah versi');
    }

    public function test_tematik_map_version_changes_after_bulk_query_operations(): void
    {
        $user = User::factory()->create();
        $a = Category::create(['type' => 'tematik', 'nama' => 'A', 'warna' => '#111111']);
        $b = Category::create(['type' => 'tematik', 'nama' => 'B', 'warna' => '#222222']);
        DataSpatial::factory()->count(2)->create(['user_id' => $user->id, 'kategori_id' => $a->id]);

        $before = $this->getJson(route('tematik.version'))->json('version');

        // Query massal (tanpa event model), seperti "Ubah Kategori/Layer" pada aksi bulk.
        DB::table('data_spatial')->update(['kategori_id' => $b->id]);
        MapDataVersion::forget();

        $this->assertNotSame($before, $this->getJson(route('tematik.version'))->json('version'));
    }

    public function test_tematik_map_version_ignores_non_tematik_categories(): void
    {
        $before = $this->getJson(route('tematik.version'))->json('version');

        Category::create(['type' => 'pokir_dprd', 'nama' => 'Bukan tematik', 'warna' => '#333333']);

        $this->assertSame($before, $this->getJson(route('tematik.version'))->json('version'));
    }
}
