<?php

namespace Tests\Feature;

use App\Models\Category as V3Category;
use App\Models\DataSpatial;
use App\Models\LegacyCategory as Category;
use App\Models\Opd;
use App\Models\Role;
use App\Models\SpatialLayer;
use App\Models\SpatialLayerFeature;
use App\Models\User;
use App\Support\MapDataVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

    public function test_map_menu_is_named_peta_interaktif(): void
    {
        $this->get(route('tampil.tentang'))
            ->assertOk()
            ->assertSee('>Peta Interaktif</a>', false)
            ->assertDontSee('>Peta Tematik</a>', false);
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
        $this->get(route('tampil.interaktif'))
            ->assertOk()
            ->assertSee('id="map"', false)
            ->assertSee('id="map-hud"', false)
            ->assertSee('id="sidebar-layer"', false)
            ->assertSee('id="btn-toggle-sidebar-basemap"', false)
            ->assertSee('id="btn-share-map"', false)
            ->assertSee('id="sidebar-layer"', false)
            ->assertSee('id="filter-kabupaten"', false)
            ->assertSee('id="filter-tahun"', false)
            ->assertSee('id="filter-opd"', false);
    }

    public function test_thematic_map_page_has_data_catalog_modal_and_active_layer_sidebar(): void
    {
        $this->get(route('tampil.interaktif'))
            ->assertOk()
            ->assertSee('id="catalogModal"', false)
            ->assertSee('id="catalog-group-list"', false)
            ->assertSee('id="catalog-items"', false)
            ->assertSee('id="catalog-search"', false)
            ->assertSee('id="catalog-apply"', false)
            ->assertSee('id="btn-open-catalog"', false)
            ->assertSee('Layer Aktif')
            ->assertSee('id="layer-list"', false)
            ->assertDontSee('id="layer-search"', false)
            ->assertSee('id="catalog-filter-toggle"', false)
            ->assertSee('id="catalog-filter-panel"', false)
            ->assertDontSee('id="btn-toggle-filter-panel"', false)
            ->assertDontSee('id="filter-panel"', false)
            ->assertSee('id="feature-drawer"', false)
            ->assertSee('id="feature-modal"', false)
            ->assertSee('frontend/js/map-feature-detail.js', false)
            ->assertSee('id="map-bottom-bar"', false)
            ->assertSee('frontend/js/map-labels.js', false)
            ->assertSee('id="mapGuide"', false)
            ->assertSee('frontend/js/map-guide.js', false)
            ->assertDontSee('id="guideModal"', false)
            ->assertDontSee('leaflet.markercluster', false)
            ->assertDontSee('id="sidebar-layer-tools"', false)
            ->assertSee('MARIMOI_FEATURE_DETAIL_URL_TEMPLATE', false)
            ->assertSee('frontend/js/map-catalog.js', false);
    }

    public function test_thematic_map_page_replaces_navbar_with_home_search_and_account_controls(): void
    {
        $this->get(route('tampil.interaktif'))
            ->assertOk()
            ->assertDontSee('id="nav"', false)
            ->assertDontSee('id="mobileMenu"', false)
            ->assertDontSee('Developed by', false)
            ->assertSee('id="app-control-buttons"', false)
            ->assertSee('id="btn-home-page" class="map-pill" href="'.route('beranda').'"', false)
            ->assertSee('id="map-search-bar"', false)
            ->assertSee('id="map-feature-search"', false)
            ->assertSee('id="btn-login" class="map-pill" href="'.route('login').'"', false)
            ->assertDontSee('id="logout-form"', false);
    }

    public function test_thematic_map_controls_show_logout_without_dashboard_for_public_user(): void
    {
        $role = Role::create(['name' => 'User', 'slug' => 'user', 'description' => null]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get(route('tampil.interaktif'))
            ->assertOk()
            ->assertSee('id="logout-form"', false)
            ->assertSee('id="btn-logout"', false)
            ->assertSee(route('logout'), false)
            ->assertDontSee('id="btn-login"', false)
            ->assertDontSee(route('dashboard'), false);
    }

    public function test_thematic_map_controls_show_dashboard_and_logout_for_admin(): void
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'description' => null]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get(route('tampil.interaktif'))
            ->assertOk()
            ->assertSee('id="btn-dashboard" class="map-pill" href="'.route('dashboard').'"', false)
            ->assertSee('id="btn-logout"', false);
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

        $response = $this->get(route('detail.interaktif', $data->uuid));

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

        $this->get(route('detail.interaktif', $data->uuid))
            ->assertOk()
            ->assertSee('id="map-detail"', false)
            ->assertSee('Fasilitas Uji')
            ->assertSee('ANGGARAN')
            ->assertSee('Rp. 1.500.000.000')
            ->assertDontSee('>ID<', false);
    }

    public function test_detail_page_shows_dataset_metadata_when_present(): void
    {
        $category = Category::create(['type' => 'tematik', 'nama' => 'Fasilitas Metadata', 'warna' => '#00ff00']);
        $user = User::factory()->create();
        $opd = Opd::create(['name' => 'Dinas Uji Coba', 'singkatan' => 'DUC']);
        $data = DataSpatial::factory()->create([
            'user_id' => $user->id,
            'kategori_id' => $category->id,
            'sumber_data' => 'Survei lapangan 2025',
            'opd_pengelola_id' => $opd->id,
            'tanggal_data' => '2025-06-01',
        ]);

        $this->get(route('detail.interaktif', $data->uuid))
            ->assertOk()
            ->assertSee('Sumber Data')
            ->assertSee('Survei lapangan 2025')
            ->assertSee('Instansi Pengelola')
            ->assertSee('Dinas Uji Coba')
            ->assertSee('Tanggal Data')
            ->assertSee('01 Jun 2025')
            ->assertDontSee('Metadata belum lengkap');
    }

    public function test_detail_page_hides_dataset_metadata_fields_when_absent(): void
    {
        $category = Category::create(['type' => 'tematik', 'nama' => 'Fasilitas Tanpa Metadata', 'warna' => '#ff00ff']);
        $user = User::factory()->create();
        $data = DataSpatial::factory()->create([
            'user_id' => $user->id,
            'kategori_id' => $category->id,
            'sumber_data' => null,
            'opd_pengelola_id' => null,
            'tanggal_data' => null,
        ]);

        $this->get(route('detail.interaktif', $data->uuid))
            ->assertOk()
            ->assertDontSee('Sumber Data')
            ->assertDontSee('Instansi Pengelola')
            ->assertDontSee('Tanggal Data')
            ->assertSee('Metadata belum lengkap');
    }

    private function publishedLayer(string $name, string $status = 'published'): SpatialLayer
    {
        $category = V3Category::create(['nama' => 'Kategori '.$name]);

        return SpatialLayer::create([
            'category_id' => $category->id,
            'layer_type_id' => 4,
            'code' => 'layer-'.Str::random(8),
            'slug' => 'layer-'.Str::random(8),
            'name' => $name,
            'status' => $status,
            'published_at' => $status === 'published' ? now() : null,
        ]);
    }

    private function mapFeature(SpatialLayer $layer): SpatialLayerFeature
    {
        return SpatialLayerFeature::create([
            'layer_id' => $layer->id,
            'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)'),
            'properties' => ['NAMA' => 'Uji'],
        ]);
    }

    public function test_tematik_map_version_is_public_and_stable_until_data_or_category_changes(): void
    {
        $layer = $this->publishedLayer('Kawasan A');
        $item = $this->mapFeature($layer);

        $version = fn () => $this->getJson(route('interaktif.version'))->assertOk()->json('version');

        $first = $version();
        $this->assertNotEmpty($first);
        $this->assertSame($first, $version(), 'versi stabil bila tidak ada perubahan');

        $this->travel(2)->seconds();
        $item->update(['properties' => ['NAMA' => 'Diubah']]);
        $afterUpdate = $version();
        $this->assertNotSame($first, $afterUpdate, 'ubah data mengubah versi');

        $this->travel(2)->seconds();
        $layer->update(['name' => 'Kawasan A (baru)']);
        $afterLayer = $version();
        $this->assertNotSame($afterUpdate, $afterLayer, 'ubah layer mengubah versi');

        $this->travel(2)->seconds();
        $this->mapFeature($layer);
        $afterAdd = $version();
        $this->assertNotSame($afterLayer, $afterAdd, 'tambah data mengubah versi');

        $item->delete();
        $this->assertNotSame($afterAdd, $version(), 'hapus data mengubah versi');
    }

    public function test_tematik_map_version_changes_after_bulk_query_operations(): void
    {
        $a = $this->publishedLayer('A');
        $b = $this->publishedLayer('B');
        $this->mapFeature($a);
        $this->mapFeature($a);

        $before = $this->getJson(route('interaktif.version'))->json('version');

        // Query massal (tanpa event model), seperti "Pindahkan data" pada aksi bulk.
        DB::table('spatial_features')->update(['layer_id' => $b->id]);
        MapDataVersion::forget();

        $this->assertNotSame($before, $this->getJson(route('interaktif.version'))->json('version'));
    }

    public function test_tematik_map_version_ignores_draft_layers(): void
    {
        $this->mapFeature($this->publishedLayer('Layer Publik'));
        $draft = $this->publishedLayer('Layer Draft', 'draft');
        $before = $this->getJson(route('interaktif.version'))->json('version');

        $this->mapFeature($draft);
        MapDataVersion::forget();

        $this->assertSame($before, $this->getJson(route('interaktif.version'))->json('version'));
    }
}
