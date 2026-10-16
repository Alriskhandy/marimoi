<?php

namespace Tests\Feature;

use App\Models\DataSpatial;
use App\Models\LegacyCategory as Category;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Fase I/D13 (plan mellow-weaving-eclipse) — retirement modul "Data Spasial"
 * lama. Mengunci 3 hal: (1) rute lama jadi redirect murni, bukan 404 maupun
 * tetap memproses lewat DataSpatialController; (2) permission `data-spatial.*`
 * benar-benar tidak ada lagi di katalog & dicabut dari role; (3) menulis ke
 * `data_spatial` legacy TIDAK LAGI direplikasi ke `spatial_features` v3 —
 * App\Support\SpatialFeaturesV3Sync (jembatan sementara, D8) sudah dihapus.
 */
class DataSpatialRetirementTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'description' => null]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_data_spatial_routes_redirect_to_spatial_layers_index(): void
    {
        $admin = $this->superAdmin();

        foreach (['data-spatial.index', 'data-spatial.map', 'data-spatial.create'] as $routeName) {
            $this->actingAs($admin)->get(route($routeName))
                ->assertRedirect(route('spatial-layers.index'));
        }
    }

    public function test_tematik_index_redirects_to_spatial_layers_index(): void
    {
        $this->actingAs($this->superAdmin())->get(route('tematik.index'))
            ->assertRedirect(route('spatial-layers.index'));
    }

    public function test_data_spatial_permission_no_longer_exists_in_catalog(): void
    {
        $this->assertNotContains('data-spatial', array_keys(Permission::CATALOG));

        foreach (Permission::catalogNames() as $name) {
            $this->assertStringStartsNotWith('data-spatial.', $name);
        }
    }

    public function test_permission_seeder_removes_pre_existing_data_spatial_permission(): void
    {
        $role = Role::create(['name' => 'Admin Bappeda', 'slug' => 'admin-bappeda', 'description' => null]);
        $legacyPermission = Permission::create(['name' => 'data-spatial.view', 'guard_name' => 'web']);
        $role->givePermissionTo($legacyPermission);

        $this->seed(PermissionSeeder::class);

        $this->assertDatabaseMissing('permissions', ['name' => 'data-spatial.view']);
        $this->assertSame(0, $role->fresh()->permissions()->where('name', 'data-spatial.view')->count());
    }

    public function test_writing_to_legacy_data_spatial_no_longer_syncs_to_v3_spatial_features(): void
    {
        $category = Category::create(['type' => 'tematik', 'nama' => 'Kategori Uji', 'warna' => '#000']);

        $feature = DataSpatial::create([
            'data_type' => 'tematik',
            'kategori_id' => $category->id,
            'deskripsi' => 'Puskesmas Uji',
            'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)'),
        ]);

        $this->assertSame(
            0,
            DB::table('spatial_features')->where('legacy_data_spatial_id', $feature->id)->count(),
            'Jembatan SpatialFeaturesV3Sync sudah dihapus — tidak boleh ada lagi baris v3 yang dibuat dari tulis legacy.'
        );
    }
}
