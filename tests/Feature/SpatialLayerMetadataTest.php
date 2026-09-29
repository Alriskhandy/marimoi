<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SpatialLayer;
use App\Models\SpatialLayerMetadata;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresi untuk metadata layer (docs/marimoi v2/04_implementation/
 * 11-plan-dashboard-skema-baru.md Bagian A).
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

    public function test_edit_page_loads_for_category_with_matching_layer(): void
    {
        $admin = $this->admin();
        $category = Category::create(['type' => 'tematik', 'nama' => 'Kategori Uji', 'warna' => '#000']);
        SpatialLayer::create([
            'slug' => 'layer-uji', 'name' => 'Kategori Uji', 'title' => 'Kategori Uji',
            'layer_class' => 'thematic', 'legacy_category_id' => $category->id,
        ]);

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
        $layer = SpatialLayer::create([
            'slug' => 'layer-uji', 'name' => 'Kategori Uji', 'title' => 'Kategori Uji',
            'layer_class' => 'thematic', 'legacy_category_id' => $category->id,
        ]);

        $this->actingAs($admin)->put(route('categories.metadata.update', $category->id), [
            'source_name' => 'BPS Maluku Utara',
            'license' => 'CC-BY-4.0',
            'data_reference_year' => 2025,
        ])->assertRedirect(route('categories.metadata.edit', $category->id));

        $this->assertDatabaseHas('spatial_layer_metadata', [
            'spatial_layer_id' => $layer->id,
            'source_name' => 'BPS Maluku Utara',
            'license' => 'CC-BY-4.0',
            'data_reference_year' => 2025,
        ]);
    }

    public function test_update_does_not_lose_existing_fields_when_omitted_from_request(): void
    {
        // Field yang tidak dikirim di request sama sekali TIDAK ikut ter-validasi
        // (bukan otomatis jadi null) — updateOrCreate() hanya menimpa key yang
        // benar-benar ada di $validated, jadi 'license' lama tetap bertahan.
        $admin = $this->admin();
        $category = Category::create(['type' => 'tematik', 'nama' => 'Kategori Uji', 'warna' => '#000']);
        $layer = SpatialLayer::create([
            'slug' => 'layer-uji', 'name' => 'Kategori Uji', 'title' => 'Kategori Uji',
            'layer_class' => 'thematic', 'legacy_category_id' => $category->id,
        ]);
        SpatialLayerMetadata::create(['spatial_layer_id' => $layer->id, 'source_name' => 'Sumber Awal', 'license' => 'CC-BY-4.0']);

        $this->actingAs($admin)->put(route('categories.metadata.update', $category->id), [
            'source_name' => 'Sumber Baru',
        ]);

        $this->assertDatabaseHas('spatial_layer_metadata', [
            'spatial_layer_id' => $layer->id,
            'source_name' => 'Sumber Baru',
            'license' => 'CC-BY-4.0',
        ]);
    }

    public function test_update_redirects_with_error_for_category_without_layer(): void
    {
        $admin = $this->admin();
        $category = Category::create(['type' => 'tematik', 'nama' => 'Kategori Tanpa Layer', 'warna' => '#000']);

        $this->actingAs($admin)->put(route('categories.metadata.update', $category->id), ['source_name' => 'X'])
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('error');
    }
}
