<?php

namespace Tests\Feature;

use App\Models\LegacyCategory as Category;
use App\Models\Opd;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SpatialLayer;
use App\Models\SpatialLayerMetadata;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regresi untuk metadata layer — skema v3. Sejak Fase C (plan
 * mellow-weaving-eclipse, implementasi spec-admin-manajemen-peta.md §5.3),
 * jalur utama adalah `spatial-layers.metadata.*` (resolve langsung dari
 * SpatialLayer) — rute lama `categories.metadata.*` kini HANYA jembatan
 * redirect supaya bookmark admin lama tidak mati (lihat
 * SpatialLayerMetadataController::editByCategory()/updateByCategory()).
 */
class SpatialLayerMetadataTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'description' => null]);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'categories.edit', 'guard_name' => 'web']));
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'spatial-layers.edit', 'guard_name' => 'web']));

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function categoryId(): string
    {
        return DB::table('categories_v3')->insertGetId([
            'id' => (string) Str::uuid(),
            'code' => 'cat-'.Str::random(8),
            'name' => 'Kategori Uji',
            'slug' => 'kategori-uji-'.Str::random(6),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id');
    }

    private function createLayer(array $overrides = []): SpatialLayer
    {
        return SpatialLayer::create(array_merge([
            'category_id' => $this->categoryId(),
            'layer_type_id' => 4,
            'code' => 'layer-'.Str::random(8),
            'slug' => 'layer-'.Str::random(8),
            'name' => 'Layer Uji',
        ], $overrides));
    }

    public function test_metadata_edit_page_loads_for_layer(): void
    {
        $admin = $this->admin();
        $layer = $this->createLayer();

        $this->actingAs($admin)->get(route('spatial-layers.metadata.edit', $layer))->assertOk();
    }

    public function test_metadata_update_saves_expanded_fields(): void
    {
        $admin = $this->admin();
        $layer = $this->createLayer();

        $this->actingAs($admin)->put(route('spatial-layers.metadata.update', $layer), [
            'title' => 'Jalan Provinsi Maluku Utara',
            'topic_category' => 'transportation',
            'producer_organization' => 'BPS Maluku Utara',
            'license' => 'CC-BY-4.0',
            'data_year' => 2025,
            'date_type' => 'publication',
            'scale_denominator' => 25000,
            'positional_accuracy' => '±5 meter',
            'administrative_area' => 'Provinsi Maluku Utara',
            'lineage' => 'Digitasi dari citra satelit 2024',
            'use_constraints' => 'Tidak untuk keperluan komersial',
            'keywords' => 'jalan, infrastruktur, provinsi',
        ])->assertRedirect(route('spatial-layers.metadata.edit', $layer));

        $this->assertDatabaseHas('layer_metadata', [
            'layer_id' => $layer->id,
            'title' => 'Jalan Provinsi Maluku Utara',
            'topic_category' => 'transportation',
            'producer_organization' => 'BPS Maluku Utara',
            'license' => 'CC-BY-4.0',
            'data_year' => 2025,
            'date_type' => 'publication',
            'scale_denominator' => 25000,
            'positional_accuracy' => '±5 meter',
            'administrative_area' => 'Provinsi Maluku Utara',
            'lineage' => 'Digitasi dari citra satelit 2024',
            'use_constraints' => 'Tidak untuk keperluan komersial',
        ]);

        $keywords = DB::table('layer_metadata')->where('layer_id', $layer->id)->value('keywords');
        $this->assertSame('{jalan,infrastruktur,provinsi}', $keywords);
    }

    public function test_metadata_edit_page_shows_saved_keywords(): void
    {
        $admin = $this->admin();
        $layer = $this->createLayer();
        SpatialLayerMetadata::create(['layer_id' => $layer->id]);
        DB::statement('UPDATE layer_metadata SET keywords = ?::text[] WHERE layer_id = ?', ['{jalan,provinsi}', $layer->id]);

        $response = $this->actingAs($admin)->get(route('spatial-layers.metadata.edit', $layer));

        $response->assertOk()->assertSee('value="jalan, provinsi"', false);
    }

    public function test_update_does_not_lose_existing_fields_when_omitted_from_request(): void
    {
        $admin = $this->admin();
        $layer = $this->createLayer();
        SpatialLayerMetadata::create(['layer_id' => $layer->id, 'producer_organization' => 'Sumber Awal', 'license' => 'CC-BY-4.0']);

        $this->actingAs($admin)->put(route('spatial-layers.metadata.update', $layer), [
            'producer_organization' => 'Sumber Baru',
        ]);

        $this->assertDatabaseHas('layer_metadata', [
            'layer_id' => $layer->id,
            'producer_organization' => 'Sumber Baru',
            'license' => 'CC-BY-4.0',
        ]);
    }

    public function test_admin_opd_cannot_access_metadata_of_layer_owned_by_another_opd(): void
    {
        $opd = Opd::create(['name' => 'Dinas Uji', 'singkatan' => Str::upper(Str::random(5))]);
        $otherOpd = Opd::create(['name' => 'Dinas Lain', 'singkatan' => Str::upper(Str::random(5))]);
        $role = Role::create(['name' => 'Admin OPD', 'slug' => 'admin-opd', 'description' => null]);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'spatial-layers.edit', 'guard_name' => 'web']));
        $user = User::factory()->create(['role_id' => $role->id, 'opd_id' => $opd->id]);
        $layer = $this->createLayer(['opd_id' => $otherOpd->id]);

        $this->actingAs($user)->get(route('spatial-layers.metadata.edit', $layer))->assertForbidden();
    }

    public function test_legacy_edit_route_redirects_to_new_metadata_page(): void
    {
        $admin = $this->admin();
        $category = Category::create(['type' => 'tematik', 'nama' => 'Kategori Uji', 'warna' => '#000']);
        $layer = $this->createLayer(['legacy_category_id' => $category->id]);

        $this->actingAs($admin)->get(route('categories.metadata.edit', $category->id))
            ->assertRedirect(route('spatial-layers.metadata.edit', $layer));
    }

    public function test_legacy_edit_redirects_with_error_for_category_without_layer(): void
    {
        $admin = $this->admin();
        $category = Category::create(['type' => 'tematik', 'nama' => 'Kategori Tanpa Layer', 'warna' => '#000']);

        $this->actingAs($admin)->get(route('categories.metadata.edit', $category->id))
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('error');
    }

    public function test_legacy_update_route_saves_and_redirects_to_new_metadata_page(): void
    {
        $admin = $this->admin();
        $category = Category::create(['type' => 'tematik', 'nama' => 'Kategori Uji', 'warna' => '#000']);
        $layer = $this->createLayer(['legacy_category_id' => $category->id]);

        $this->actingAs($admin)->put(route('categories.metadata.update', $category->id), [
            'producer_organization' => 'BPS Maluku Utara',
            'license' => 'CC-BY-4.0',
            'data_year' => 2025,
        ])->assertRedirect(route('spatial-layers.metadata.edit', $layer));

        $this->assertDatabaseHas('layer_metadata', [
            'layer_id' => $layer->id,
            'producer_organization' => 'BPS Maluku Utara',
            'license' => 'CC-BY-4.0',
            'data_year' => 2025,
        ]);
    }

    public function test_legacy_update_redirects_with_error_for_category_without_layer(): void
    {
        $admin = $this->admin();
        $category = Category::create(['type' => 'tematik', 'nama' => 'Kategori Tanpa Layer', 'warna' => '#000']);

        $this->actingAs($admin)->put(route('categories.metadata.update', $category->id), ['producer_organization' => 'X'])
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('error');
    }
}
