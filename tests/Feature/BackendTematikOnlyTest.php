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
            ->assertSee('id="mapSearchInput"', false)
            ->assertSee(route('data-spatial.index', ['type' => 'tematik']), false);

        $this->actingAs($admin)
            ->get(route('data-spatial.index', ['type' => 'tematik']))
            ->assertOk()
            ->assertDontSee('id="dataSpasialMap"', false)
            ->assertSee(route('data-spatial.map'), false);
    }
}
