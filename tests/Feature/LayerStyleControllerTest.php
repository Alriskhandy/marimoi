<?php

namespace Tests\Feature;

use App\Models\LayerStyle;
use App\Models\MapType;
use App\Models\MetadataDefinition;
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
            'code' => 'cat-'.Str::random(8),
            'name' => 'Kategori Uji',
            'slug' => 'kategori-uji-'.Str::random(6),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id');
    }

    private function layer(array $overrides = []): SpatialLayer
    {
        $jenis = MapType::where('slug', 'tematik')->firstOrFail();

        return SpatialLayer::create(array_merge([
            'category_id' => $this->categoryId(),
            'layer_type_id' => 4,
            'code' => 'layer-'.Str::random(8),
            'slug' => 'layer-'.Str::random(8),
            'name' => 'Layer Uji',
            'map_type_id' => $jenis->id,
        ], $overrides));
    }

    private function attachDefinition(SpatialLayer $layer, string $kode, array $definitionOverrides = []): void
    {
        $definition = MetadataDefinition::firstOrCreate(
            ['kode' => $kode],
            array_merge(['label' => ucfirst($kode)], $definitionOverrides)
        );

        $layer->mapType->dynamicAttributes()->create(['metadata_definition_id' => $definition->id]);
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

    public function test_admin_can_create_a_categorized_style_with_classes(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $this->attachDefinition($layer, 'kondisi', ['label' => 'Kondisi']);

        $this->actingAs($admin)->post(route('spatial-layers.styles.store', $layer), [
            'name' => 'Per Kondisi',
            'style_type' => 'categorized',
            'classification_field' => 'kondisi',
            'classes' => [
                ['value' => 'Baik', 'color' => '#00ff00', 'label' => 'Baik'],
                ['value' => 'Buruk', 'color' => '#ff0000', 'label' => 'Buruk'],
            ],
        ])->assertRedirect(route('spatial-layers.styles.index', $layer));

        $style = LayerStyle::where('name', 'Per Kondisi')->firstOrFail();
        $this->assertSame('kondisi', $style->classification_field);
        $this->assertCount(2, $style->definition['classes']);
        $this->assertCount(2, $style->legend);
        $this->assertSame('Baik', $style->legend[0]['label']);
    }

    public function test_graduated_style_stores_min_max_classes(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $this->attachDefinition($layer, 'panjang_km', ['label' => 'Panjang (km)']);

        $this->actingAs($admin)->post(route('spatial-layers.styles.store', $layer), [
            'name' => 'Per Panjang',
            'style_type' => 'graduated',
            'classification_field' => 'panjang_km',
            'classes' => [
                ['min' => 0, 'max' => 10, 'color' => '#ffff00'],
                ['min' => 10, 'max' => 50, 'color' => '#ff8800'],
            ],
        ])->assertRedirect(route('spatial-layers.styles.index', $layer));

        $style = LayerStyle::where('name', 'Per Panjang')->firstOrFail();
        $this->assertSame(0, (int) $style->definition['classes'][0]['min']);
        $this->assertSame(10, (int) $style->definition['classes'][0]['max']);
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
        $this->attachDefinition($layer, 'kondisi', ['label' => 'Kondisi']);

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
