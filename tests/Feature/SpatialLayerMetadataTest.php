<?php

namespace Tests\Feature;

use App\Models\LegacyCategory as Category;
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
 * Regresi untuk metadata layer — skema v3 (plan mellow-weaving-eclipse Fase
 * 3). Tabel fisik berganti nama (`spatial_layer_metadata` -> `layer_metadata`),
 * PK berubah dari `spatial_layer_id` jadi `layer_id`, dan field
 * `source_name`/`data_reference_year` dipetakan ulang ke
 * `producer_organization`/`data_year` (lihat SpatialLayerMetadataController).
 */
class SpatialLayerMetadataTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'description' => null]);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'categories.edit', 'guard_name' => 'web']));

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
            'slug' => 'layer-uji',
            'name' => 'Kategori Uji',
        ], $overrides));
    }

    public function test_edit_page_loads_for_category_with_matching_layer(): void
    {
        $admin = $this->admin();
        $category = Category::create(['type' => 'tematik', 'nama' => 'Kategori Uji', 'warna' => '#000']);
        $this->createLayer(['legacy_category_id' => $category->id]);

        $this->actingAs($admin)->get(route('categories.metadata.edit', $category->id))->assertOk();
    }

    public function test_edit_redirects_with_error_for_category_without_layer(): void
    {
        $admin = $this->admin();
        $category = Category::create(['type' => 'tematik', 'nama' => 'Kategori Tanpa Layer', 'warna' => '#000']);

        $this->actingAs($admin)->get(route('categories.metadata.edit', $category->id))
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('error');
    }

    public function test_update_saves_metadata_for_category_with_layer(): void
    {
        $admin = $this->admin();
        $category = Category::create(['type' => 'tematik', 'nama' => 'Kategori Uji', 'warna' => '#000']);
        $layer = $this->createLayer(['legacy_category_id' => $category->id]);

        $this->actingAs($admin)->put(route('categories.metadata.update', $category->id), [
            'producer_organization' => 'BPS Maluku Utara',
            'license' => 'CC-BY-4.0',
            'data_year' => 2025,
        ])->assertRedirect(route('categories.metadata.edit', $category->id));

        $this->assertDatabaseHas('layer_metadata', [
            'layer_id' => $layer->id,
            'producer_organization' => 'BPS Maluku Utara',
            'license' => 'CC-BY-4.0',
            'data_year' => 2025,
        ]);
    }

    public function test_update_does_not_lose_existing_fields_when_omitted_from_request(): void
    {
        // Field yang tidak dikirim di request sama sekali TIDAK ikut ter-validasi
        // (bukan otomatis jadi null) — updateOrCreate() hanya menimpa key yang
        // benar-benar ada di $validated, jadi 'license' lama tetap bertahan.
        $admin = $this->admin();
        $category = Category::create(['type' => 'tematik', 'nama' => 'Kategori Uji', 'warna' => '#000']);
        $layer = $this->createLayer(['legacy_category_id' => $category->id]);
        SpatialLayerMetadata::create(['layer_id' => $layer->id, 'producer_organization' => 'Sumber Awal', 'license' => 'CC-BY-4.0']);

        $this->actingAs($admin)->put(route('categories.metadata.update', $category->id), [
            'producer_organization' => 'Sumber Baru',
        ]);

        $this->assertDatabaseHas('layer_metadata', [
            'layer_id' => $layer->id,
            'producer_organization' => 'Sumber Baru',
            'license' => 'CC-BY-4.0',
        ]);
    }

    public function test_update_redirects_with_error_for_category_without_layer(): void
    {
        $admin = $this->admin();
        $category = Category::create(['type' => 'tematik', 'nama' => 'Kategori Tanpa Layer', 'warna' => '#000']);

        $this->actingAs($admin)->put(route('categories.metadata.update', $category->id), ['producer_organization' => 'X'])
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('error');
    }
}
