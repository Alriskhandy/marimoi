<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DataSpatial;
use App\Models\Permission;
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

    public function test_geojson_can_be_loaded_in_batches_without_duplicates_or_gaps(): void
    {
        $admin = $this->superAdmin();
        $category = $this->category();

        DataSpatial::factory()->count(5)->create([
            'user_id' => $admin->id,
            'kategori_id' => $category->id,
        ]);

        $uuids = [];

        foreach ([0, 2, 4] as $offset) {
            $response = $this->actingAs($admin)->getJson(route('data-spatial.geojson', [
                'data_type' => 'tematik',
                'category_id' => $category->id,
                'limit' => 2,
                'offset' => $offset,
            ]));

            $response->assertOk();
            $response->assertJsonPath('meta.total_matching', 5);
            $response->assertJsonPath('meta.has_more', $offset < 4);

            $uuids = array_merge($uuids, array_column(array_column($response->json('features'), 'properties'), 'uuid'));
        }

        $this->assertCount(5, $uuids);
        $this->assertCount(5, array_unique($uuids));
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
        $role->givePermissionTo(Permission::create(['name' => 'data-spatial.view']));
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

    public function test_guest_cannot_access_geojson_version_endpoint(): void
    {
        $this->get(route('data-spatial.geojson-version'))->assertRedirect(route('login'));
    }

    public function test_geojson_version_reports_total_and_changes_when_layer_data_changes(): void
    {
        $admin = $this->superAdmin();
        $category = $this->category();
        $url = fn () => route('data-spatial.geojson-version', ['data_type' => 'tematik', 'category_id' => $category->id]);

        $item = DataSpatial::factory()->create(['user_id' => $admin->id, 'kategori_id' => $category->id]);

        $first = $this->actingAs($admin)->getJson($url())->assertOk()->assertJsonPath('total', 1)->json('version');
        $this->assertSame($first, $this->actingAs($admin)->getJson($url())->json('version'), 'versi harus stabil bila data tidak berubah');

        $this->travel(2)->seconds();
        DataSpatial::factory()->create(['user_id' => $admin->id, 'kategori_id' => $category->id]);
        $afterAdd = $this->actingAs($admin)->getJson($url())->assertJsonPath('total', 2)->json('version');
        $this->assertNotSame($first, $afterAdd);

        $this->travel(2)->seconds();
        $category->update(['nama' => 'Fasilitas Umum Baru']);
        $afterRename = $this->actingAs($admin)->getJson($url())->json('version');
        $this->assertNotSame($afterAdd, $afterRename);

        $this->travel(2)->seconds();
        $item->delete();
        $afterDelete = $this->actingAs($admin)->getJson($url())->assertJsonPath('total', 1)->json('version');
        $this->assertNotSame($afterRename, $afterDelete);
    }

    public function test_geojson_version_only_counts_own_data_for_non_admin_roles(): void
    {
        $role = Role::create(['name' => 'Admin OPD', 'slug' => 'admin-opd', 'description' => null]);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'data-spatial.view', 'guard_name' => 'web']));
        $opd = User::factory()->create(['role_id' => $role->id]);
        $other = User::factory()->create(['role_id' => $role->id]);
        $category = $this->category();

        DataSpatial::factory()->create(['user_id' => $opd->id, 'kategori_id' => $category->id]);
        DataSpatial::factory()->count(2)->create(['user_id' => $other->id, 'kategori_id' => $category->id]);

        $this->actingAs($opd)
            ->getJson(route('data-spatial.geojson-version', ['category_id' => $category->id]))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('user', $opd->id);
    }
}
