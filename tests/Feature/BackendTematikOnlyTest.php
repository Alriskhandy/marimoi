<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class BackendTematikOnlyTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'description' => null]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_legacy_backend_routes_are_removed(): void
    {
        foreach (['psd.index', 'psn.index', 'pokir-dprd.index', 'usulan-musrenbang.index'] as $routeName) {
            $this->assertFalse(Route::has($routeName), $routeName);
        }
    }

    public function test_sidebar_only_lists_peta_tematik_menu(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->get(route('data-spatial.index', ['type' => 'tematik']));

        $response->assertOk();
        $response->assertSee('Peta Tematik');

        foreach (['#pokirMenu', '#usulanmusrenbang', 'Data Pokir DPRD', 'Data Usulan Musrenbang', 'Kategori Proyek Nasional'] as $label) {
            $response->assertDontSee($label);
        }
    }

    public function test_data_spatial_index_rejects_legacy_types(): void
    {
        $admin = $this->superAdmin();

        foreach (['proyek_strategis', 'pokir_dprd', 'usulan_musrenbang'] as $type) {
            $this->actingAs($admin)
                ->from(route('dashboard'))
                ->get(route('data-spatial.index', ['type' => $type]))
                ->assertRedirect(route('dashboard'));
        }
    }

    public function test_category_index_rejects_legacy_types(): void
    {
        $this->actingAs($this->superAdmin())
            ->from(route('dashboard'))
            ->get(route('categories.index', ['type' => 'psd']))
            ->assertRedirect(route('dashboard'));
    }

    public function test_map_and_table_views_are_separate_pages(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->get(route('data-spatial.map'))
            ->assertOk()
            ->assertSee('id="dataSpasialMap"', false)
            ->assertSee('data-basemap="satelit"', false)
            ->assertSee('id="toolDistance"', false)
            ->assertSee('id="toolPoint"', false)
            ->assertSee('data-format="kmz"', false)
            ->assertSee('id="mapSearchInput"', false)
            ->assertSee(route('data-spatial.index', ['type' => 'tematik']), false);

        $this->actingAs($admin)
            ->get(route('data-spatial.index', ['type' => 'tematik']))
            ->assertOk()
            ->assertDontSee('id="dataSpasialMap"', false)
            ->assertSee(route('data-spatial.map'), false);
    }

    /**
     * @return array<string, mixed>
     */
    private function drawingPayload(string $format): array
    {
        return [
            'format' => $format,
            'features' => [
                ['type' => 'Point', 'name' => 'Titik 1', 'coordinates' => [127.38, 0.79]],
                ['type' => 'LineString', 'name' => 'Garis 1', 'coordinates' => [[127.38, 0.79], [127.4, 0.8]]],
                ['type' => 'Polygon', 'name' => 'Poligon <1>', 'coordinates' => [[[127.3, 0.7], [127.4, 0.7], [127.4, 0.8], [127.3, 0.7]]]],
            ],
        ];
    }

    public function test_drawings_can_be_downloaded_as_kml(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->post(route('data-spatial.export-drawings'), $this->drawingPayload('kml'));

        $response->assertOk();
        $response->assertHeader('Content-Disposition', 'attachment; filename="gambar-peta.kml"');

        $kml = $response->getContent();
        $this->assertStringContainsString('<Point><coordinates>127.38,0.79,0</coordinates></Point>', $kml);
        $this->assertStringContainsString('<LineString>', $kml);
        $this->assertStringContainsString('<Polygon>', $kml);
        $this->assertStringContainsString('Poligon &lt;1&gt;', $kml);
        $this->assertNotFalse(simplexml_load_string($kml));
    }

    public function test_drawings_can_be_downloaded_as_kmz(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->post(route('data-spatial.export-drawings'), $this->drawingPayload('kmz'));

        $response->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'kmz-test');
        file_put_contents($path, $response->baseResponse->getFile()->getContent());

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($path));
        $this->assertStringContainsString('<Placemark>', $zip->getFromName('doc.kml'));
        $zip->close();
        unlink($path);
    }

    public function test_drawing_export_rejects_invalid_coordinates(): void
    {
        $payload = $this->drawingPayload('kml');
        $payload['features'][0]['coordinates'] = [999, 0.79];

        $this->actingAs($this->superAdmin())
            ->postJson(route('data-spatial.export-drawings'), $payload)
            ->assertUnprocessable();
    }

    public function test_guest_cannot_export_drawings(): void
    {
        $this->post(route('data-spatial.export-drawings'), $this->drawingPayload('kml'))
            ->assertRedirect(route('login'));
    }
}
