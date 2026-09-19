<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DataSpatial;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataSpatialGeojsonTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'description' => null]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function category(): Category
    {
        return Category::create([
            'type' => 'tematik',
            'nama' => 'Fasilitas Umum',
            'warna' => '#0d6efd',
            'is_marker' => true,
        ]);
    }

    public function test_guest_cannot_access_geojson_endpoint(): void
    {
        $response = $this->get(route('data-spatial.geojson', ['data_type' => 'tematik']));

        $response->assertRedirect(route('login'));
    }

    public function test_geojson_returns_feature_collection_with_uuid_for_editing_and_deleting(): void
    {
        $admin = $this->superAdmin();
        $category = $this->category();

        $dataSpatial = DataSpatial::factory()->create([
            'user_id' => $admin->id,
            'kategori_id' => $category->id,
        ]);

        $response = $this->actingAs($admin)->getJson(
            route('data-spatial.geojson', ['data_type' => 'tematik'])
        );

        $response->assertOk();
        $response->assertJsonPath('type', 'FeatureCollection');
        $response->assertJsonPath('features.0.properties.uuid', $dataSpatial->uuid);
        $response->assertJsonPath('features.0.properties.kategori', $category->nama);
    }

    public function test_geojson_caps_results_and_reports_truncation_for_large_datasets(): void
    {
        $admin = $this->superAdmin();
        $category = $this->category();

        DataSpatial::factory()->count(5)->create([
            'user_id' => $admin->id,
            'kategori_id' => $category->id,
        ]);

        $response = $this->actingAs($admin)->getJson(
            route('data-spatial.geojson', ['data_type' => 'tematik', 'limit' => 2])
        );

        $response->assertOk();
        $response->assertJsonCount(2, 'features');
        $response->assertJsonPath('meta.total_matching', 5);
        $response->assertJsonPath('meta.truncated', true);
    }

    public function test_tematik_map_page_renders_layer_checklist(): void
    {
        $admin = $this->superAdmin();
        $category = $this->category();

        $response = $this->actingAs($admin)->get(route('data-spatial.map'));

        $response->assertOk();
        $response->assertSee('id="dataSpasialMap"', false);
        $response->assertSee('map-layer-checkbox', false);
        $response->assertSee('id="layer-cat-'.$category->id.'"', false);
        $response->assertSee($category->nama);
    }

    public function test_non_admin_only_sees_their_own_data_on_the_map(): void
    {
        $role = Role::create(['name' => 'Admin OPD', 'slug' => 'admin-opd', 'description' => null]);
        $owner = User::factory()->create(['role_id' => $role->id]);
        $otherUser = User::factory()->create(['role_id' => $role->id]);
        $category = $this->category();

        DataSpatial::factory()->create(['user_id' => $otherUser->id, 'kategori_id' => $category->id]);
        $ownData = DataSpatial::factory()->create(['user_id' => $owner->id, 'kategori_id' => $category->id]);

        $response = $this->actingAs($owner)->getJson(
            route('data-spatial.geojson', ['data_type' => 'tematik'])
        );

        $response->assertOk();
        $features = $response->json('features');

        $this->assertCount(1, $features);
        $this->assertSame($ownData->uuid, $features[0]['properties']['uuid']);
    }
}
