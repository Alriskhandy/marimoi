<?php

namespace Tests\Feature;

use App\Models\LayerStyle;
use App\Models\Opd;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SpatialLayer;
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
}
