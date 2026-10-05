<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\SpatialLayer;
use App\Models\SpatialLayerFeature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regresi Prioritas 8 (migrasi kode Category/DataSpatial -> SpatialLayer/
 * SpatialLayerFeature): DashboardController::index()/getDashboardStats() sempat
 * menghitung `totalLokasi` dari DataSpatial::count(), sekarang dari
 * SpatialLayerFeature::count() — jumlahnya harus tetap benar setelah swap.
 */
class DashboardTotalLokasiTest extends TestCase
{
    use RefreshDatabase;

    private function roleWith(string $slug, array $permissions = []): Role
    {
        $role = Role::create(['name' => ucfirst($slug), 'slug' => $slug, 'description' => null]);

        foreach ($permissions as $name) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }

        return $role;
    }

    private function makeFeature(?int $createdBy = null): SpatialLayerFeature
    {
        $categoryId = DB::table('categories_v3')->insertGetId([
            'id' => (string) Str::uuid(),
            'name' => 'Kategori Uji',
            'slug' => 'kategori-uji-'.Str::random(6),
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id');

        $layer = SpatialLayer::create([
            'category_id' => $categoryId,
            'layer_type_id' => 4,
            'code' => 'layer-'.Str::random(8),
            'slug' => 'layer-'.uniqid(),
            'name' => 'Layer',
        ]);

        return SpatialLayerFeature::create([
            'layer_id' => $layer->id,
            'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)'),
            'created_by' => $createdBy,
        ]);
    }

    public function test_dashboard_page_shows_correct_total_lokasi_from_spatial_layer_features(): void
    {
        $admin = User::factory()->create(['role_id' => $this->roleWith('super-admin', ['dashboard.view'])->id]);
        $this->makeFeature();
        $this->makeFeature();
        $this->makeFeature();

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('totalLokasi', 3);
    }

    public function test_admin_opd_only_sees_lokasi_they_created(): void
    {
        $opdRole = $this->roleWith('admin-opd', ['dashboard.view']);
        $admin = User::factory()->create(['role_id' => $opdRole->id]);
        $otherAdmin = User::factory()->create(['role_id' => $opdRole->id]);

        $this->makeFeature($admin->id);
        $this->makeFeature($admin->id);
        $this->makeFeature($otherAdmin->id);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('totalLokasi', 2);
    }

    public function test_dashboard_stats_endpoint_returns_correct_total_lokasi(): void
    {
        $admin = User::factory()->create(['role_id' => $this->roleWith('super-admin', ['dashboard.view'])->id]);
        $this->makeFeature();
        $this->makeFeature();

        $response = $this->actingAs($admin)->getJson(route('dashboard.api.stats'));

        $response->assertOk();
        $response->assertJsonPath('data.totalLokasi', 2);
    }
}
