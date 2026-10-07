<?php

namespace Tests\Feature;

use App\Models\LayerStyle;
use App\Models\Opd;
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
 * Fase F (spec-admin-manajemen-peta.md §5.6) — style categorized/graduated
 * dan beberapa style per layer dengan satu default.
 */
class LayerStyleControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'description' => null]);
        foreach (['spatial-layers.view', 'spatial-layers.edit'] as $perm) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']));
        }

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function categoryId(): string
    {
        return DB::table('categories_v3')->insertGetId([
            'id' => (string) Str::uuid(),
            'name' => 'Kategori Uji',
            'slug' => 'kategori-uji-'.Str::random(6),
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id');
    }

    private function layer(array $overrides = []): SpatialLayer
    {
        return SpatialLayer::create(array_merge([
            'category_id' => $this->categoryId(),
            'layer_type_id' => 4,
            'code' => 'layer-'.Str::random(8),
            'slug' => 'layer-'.Str::random(8),
            'name' => 'Layer Uji',
        ], $overrides));
    }

    public function test_index_page_renders(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();

        $this->actingAs($admin)->get(route('spatial-layers.styles.index', $layer))->assertOk();
    }

    public function test_admin_can_create_a_simple_style(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();

        $this->actingAs($admin)->post(route('spatial-layers.styles.store', $layer), [
            'name' => 'Simple A',
            'style_type' => 'simple',
            'color' => '#ff0000',
        ])->assertRedirect(route('spatial-layers.styles.index', $layer));

        $this->assertDatabaseHas('layer_styles', [
            'layer_id' => $layer->id,
            'name' => 'Simple A',
            'style_type' => 'simple',
        ]);
    }

    /**
     * Regresi: sejak `layers.map_type_id` dihapus (2026-10-06, lihat migration
     * drop_map_type_id_and_visibility_from_layers_table), `classificationFieldsFor()`
     * selalu mengembalikan collection kosong untuk SEMUA Layer — artinya
     * `classification_field` tidak pernah lolos validasi `Rule::in([])` lagi,
     * jadi style categorized/graduated tidak bisa lagi dibuat sama sekali.
     * Menggantikan test_admin_can_create_a_categorized_style_with_classes dan
     * test_graduated_style_stores_min_max_classes yang menguji perilaku lama
     * (field klasifikasi valid) — perilaku itu sengaja sudah tidak bisa
     * terjadi lagi, bukan regresi.
     */
    public function test_categorized_and_graduated_styles_can_no_longer_be_created(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();

        $this->actingAs($admin)->post(route('spatial-layers.styles.store', $layer), [
            'name' => 'Per Kondisi',
            'style_type' => 'categorized',
            'classification_field' => 'kondisi',
            'classes' => [['value' => 'Baik', 'color' => '#00ff00']],
        ])->assertSessionHasErrors('classification_field');

        $this->actingAs($admin)->post(route('spatial-layers.styles.store', $layer), [
            'name' => 'Per Panjang',
            'style_type' => 'graduated',
            'classification_field' => 'panjang_km',
            'classes' => [['min' => 0, 'max' => 10, 'color' => '#ffff00']],
        ])->assertSessionHasErrors('classification_field');
    }

    public function test_classification_field_is_required_for_categorized_style(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();

        $this->actingAs($admin)->post(route('spatial-layers.styles.store', $layer), [
            'name' => 'Tanpa Field',
            'style_type' => 'categorized',
        ])->assertSessionHasErrors('classification_field');
    }

    public function test_classification_field_must_be_a_known_dynamic_attribute(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();

        $this->actingAs($admin)->post(route('spatial-layers.styles.store', $layer), [
            'name' => 'Field Salah',
            'style_type' => 'categorized',
            'classification_field' => 'bukan_atribut_terdaftar',
            'classes' => [['value' => 'x', 'color' => '#000']],
        ])->assertSessionHasErrors('classification_field');
    }

    public function test_style_name_must_be_unique_per_layer(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        LayerStyle::create(['layer_id' => $layer->id, 'name' => 'Dipakai', 'style_type' => 'simple', 'definition' => ['color' => '#000']]);

        $this->actingAs($admin)->post(route('spatial-layers.styles.store', $layer), [
            'name' => 'Dipakai',
            'style_type' => 'simple',
        ])->assertSessionHasErrors('name');
    }

    public function test_setting_a_style_as_default_demotes_previous_default_and_syncs_layer(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $styleA = LayerStyle::create(['layer_id' => $layer->id, 'name' => 'A', 'style_type' => 'simple', 'definition' => ['color' => '#000'], 'is_default' => true]);
        $layer->update(['default_style_id' => $styleA->id]);

        $this->actingAs($admin)->post(route('spatial-layers.styles.store', $layer), [
            'name' => 'B',
            'style_type' => 'simple',
            'color' => '#fff',
            'is_default' => '1',
        ])->assertRedirect(route('spatial-layers.styles.index', $layer));

        $styleB = LayerStyle::where('name', 'B')->firstOrFail();
        $this->assertFalse($styleA->fresh()->is_default);
        $this->assertTrue($styleB->is_default);
        $this->assertSame($styleB->id, $layer->fresh()->default_style_id);
    }

    public function test_cannot_delete_the_default_style(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $style = LayerStyle::create(['layer_id' => $layer->id, 'name' => 'Default', 'style_type' => 'simple', 'definition' => ['color' => '#000'], 'is_default' => true]);
        $layer->update(['default_style_id' => $style->id]);

        $this->actingAs($admin)->delete(route('spatial-layers.styles.destroy', [$layer, $style]))
            ->assertRedirect(route('spatial-layers.styles.index', $layer))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('layer_styles', ['id' => $style->id]);
    }

    public function test_can_delete_a_non_default_style(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $default = LayerStyle::create(['layer_id' => $layer->id, 'name' => 'Default', 'style_type' => 'simple', 'definition' => ['color' => '#000'], 'is_default' => true]);
        $layer->update(['default_style_id' => $default->id]);
        $extra = LayerStyle::create(['layer_id' => $layer->id, 'name' => 'Lain', 'style_type' => 'simple', 'definition' => ['color' => '#111']]);

        $this->actingAs($admin)->delete(route('spatial-layers.styles.destroy', [$layer, $extra]))
            ->assertRedirect(route('spatial-layers.styles.index', $layer));

        $this->assertDatabaseMissing('layer_styles', ['id' => $extra->id]);
    }

    public function test_simple_style_persists_size(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();

        $this->actingAs($admin)->post(route('spatial-layers.styles.store', $layer), [
            'name' => 'Simple Ukuran',
            'style_type' => 'simple',
            'color' => '#ff0000',
            'size' => 12,
        ])->assertRedirect(route('spatial-layers.styles.index', $layer));

        $style = LayerStyle::where('name', 'Simple Ukuran')->firstOrFail();
        $this->assertEquals(12, $style->definition['size']);
    }

    public function test_update_redirects_back_to_layer_show_when_return_to_show_is_set(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $style = LayerStyle::create(['layer_id' => $layer->id, 'name' => 'Default', 'style_type' => 'simple', 'definition' => ['color' => '#000'], 'is_default' => true]);
        $layer->update(['default_style_id' => $style->id]);

        $this->actingAs($admin)->put(route('spatial-layers.styles.update', [$layer, $style]), [
            'name' => 'Default',
            'style_type' => 'simple',
            'is_default' => '1',
            'color' => '#00ff00',
            'opacity' => 0.5,
            'size' => 8,
            'return_to_show' => '1',
        ])->assertRedirect(route('spatial-layers.show', $layer));

        $style->refresh();
        $this->assertSame('#00ff00', $style->definition['color']);
        $this->assertEquals(8, $style->definition['size']);
    }

    public function test_update_still_redirects_to_styles_index_without_return_to_show(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $style = LayerStyle::create(['layer_id' => $layer->id, 'name' => 'Default', 'style_type' => 'simple', 'definition' => ['color' => '#000'], 'is_default' => true]);
        $layer->update(['default_style_id' => $style->id]);

        $this->actingAs($admin)->put(route('spatial-layers.styles.update', [$layer, $style]), [
            'name' => 'Default',
            'style_type' => 'simple',
            'is_default' => '1',
            'color' => '#00ff00',
        ])->assertRedirect(route('spatial-layers.styles.index', $layer));
    }

    public function test_admin_opd_cannot_manage_styles_of_another_opd_layer(): void
    {
        $opd = Opd::create(['name' => 'Dinas Uji', 'singkatan' => Str::upper(Str::random(5))]);
        $otherOpd = Opd::create(['name' => 'Dinas Lain', 'singkatan' => Str::upper(Str::random(5))]);
        $role = Role::create(['name' => 'Admin OPD', 'slug' => 'admin-opd', 'description' => null]);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'spatial-layers.view', 'guard_name' => 'web']));
        $user = User::factory()->create(['role_id' => $role->id, 'opd_id' => $opd->id]);
        $layer = $this->layer(['opd_id' => $otherOpd->id]);

        $this->actingAs($user)->get(route('spatial-layers.styles.index', $layer))->assertForbidden();
    }

    /**
     * Custom style per Data Spasial (style_override) sengaja disatukan di
     * halaman INI bersama style default Layer — sebelumnya tersebar ke
     * halaman "Kelola Data Spasial" terpisah (lihat
     * SpatialLayerFeatureControllerTest), yang membuat admin harus bolak-
     * balik dua tempat untuk satu urusan (style).
     */
    public function test_index_page_lists_features_with_their_effective_style(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        SpatialLayerFeature::create([
            'layer_id' => $layer->id,
            'label' => 'Data Uji',
            'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)'),
        ]);

        $this->actingAs($admin)->get(route('spatial-layers.styles.index', $layer))
            ->assertOk()
            ->assertSee('Data Uji')
            ->assertSee('Custom Style per Data Spasial');
    }

    public function test_update_style_sets_feature_override(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $feature = SpatialLayerFeature::create(['layer_id' => $layer->id, 'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)')]);

        $this->actingAs($admin)->put(route('spatial-layers.features.update-style', [$layer, $feature]), [
            'custom_style' => '1',
            'style_color' => '#ff0000',
            'style_size' => 10,
            'style_opacity' => 0.5,
        ])->assertRedirect(route('spatial-layers.styles.index', $layer));

        $feature->refresh();
        $this->assertSame('#ff0000', $feature->style_override['color']);
        $this->assertEquals(10.0, $feature->style_override['size']);
        $this->assertEquals(0.5, $feature->style_override['opacity']);
        $this->assertFalse($feature->style_override['is_marker']);
    }

    public function test_update_style_without_custom_flag_clears_existing_override(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $feature = SpatialLayerFeature::create([
            'layer_id' => $layer->id,
            'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)'),
            'style_override' => ['color' => '#ff0000', 'size' => 10, 'opacity' => 0.5, 'is_marker' => false, 'icon' => null],
        ]);

        $this->actingAs($admin)->put(route('spatial-layers.features.update-style', [$layer, $feature]), [
            'custom_style' => '0',
        ])->assertRedirect(route('spatial-layers.styles.index', $layer));

        $this->assertNull($feature->refresh()->style_override);
    }

    /**
     * Edit style langsung dari popup di peta (spatial-layers/show.blade.php)
     * memanggil endpoint yang sama via fetch() dengan `Accept: application/json`
     * — dipakai supaya popup bisa menyimpan tanpa reload halaman (redirect
     * biasa akan membuang state peta: posisi, zoom, basemap aktif).
     */
    public function test_update_style_returns_json_when_request_wants_json(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $feature = SpatialLayerFeature::create(['layer_id' => $layer->id, 'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)')]);

        $response = $this->actingAs($admin)->putJson(route('spatial-layers.features.update-style', [$layer, $feature]), [
            'custom_style' => '1',
            'style_color' => '#00ff00',
            'style_size' => 8,
            'style_opacity' => 0.7,
        ]);

        $response->assertOk()->assertJson(['style_override' => ['color' => '#00ff00']]);
        $this->assertSame('#00ff00', $feature->refresh()->style_override['color']);
    }

    public function test_admin_opd_cannot_update_style_of_feature_on_another_opd_layer(): void
    {
        $opd = Opd::create(['name' => 'Dinas Uji', 'singkatan' => Str::upper(Str::random(5))]);
        $otherOpd = Opd::create(['name' => 'Dinas Lain', 'singkatan' => Str::upper(Str::random(5))]);
        $role = Role::create(['name' => 'Admin OPD', 'slug' => 'admin-opd', 'description' => null]);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'spatial-layers.edit', 'guard_name' => 'web']));
        $user = User::factory()->create(['role_id' => $role->id, 'opd_id' => $opd->id]);
        $layer = $this->layer(['opd_id' => $otherOpd->id]);
        $feature = SpatialLayerFeature::create(['layer_id' => $layer->id, 'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)')]);

        $this->actingAs($user)->put(route('spatial-layers.features.update-style', [$layer, $feature]), [
            'custom_style' => '1',
            'style_color' => '#ff0000',
            'style_size' => 10,
            'style_opacity' => 0.5,
        ])->assertForbidden();
    }
}
