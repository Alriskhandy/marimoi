<?php

namespace Tests\Feature;

use App\Models\MapType;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SpatialLayer;
use App\Models\SpatialLayerFeature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regresi untuk SpatialLayerFeatureController (docs/marimoi v2/04_implementation/
 * 12-implementasi-perbaikan-pemetaan.md Bagian 3.2) — attributes (impor) vs
 * metadata_dinamis (terstruktur) tersimpan terpisah, is_wajib divalidasi.
 */
class SpatialLayerFeatureControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'description' => null]);
        foreach (['spatial-layers.view', 'spatial-layers.create', 'spatial-layers.edit', 'spatial-layers.delete'] as $perm) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']));
        }

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function layer(array $overrides = []): SpatialLayer
    {
        $jenis = MapType::where('slug', 'tematik')->firstOrFail();

        return SpatialLayer::create(array_merge([
            'slug' => 'layer-'.uniqid(),
            'name' => 'Layer Uji',
            'title' => 'Layer Uji',
            'layer_class' => 'thematic',
            'map_type_id' => $jenis->id,
        ], $overrides));
    }

    public function test_admin_can_create_a_feature_with_wkt_geometry(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();

        $this->actingAs($admin)->post(route('spatial-layers.features.store', $layer), [
            'geometry_wkt' => 'POINT(127.5 0.8)',
        ])->assertRedirect(route('spatial-layers.show', $layer));

        $this->assertSame(1, $layer->features()->count());
    }

    public function test_required_dynamic_attribute_is_enforced(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $layer->mapType->dynamicAttributes()->create([
            'tipe' => 'placeholder', 'kode_atribut' => 'pagu', 'label' => 'Pagu', 'is_wajib' => true,
        ]);

        $this->actingAs($admin)->post(route('spatial-layers.features.store', $layer), [
            'geometry_wkt' => 'POINT(127.5 0.8)',
        ])->assertSessionHasErrors('metadata_dinamis.pagu');
    }

    public function test_optional_dynamic_attribute_can_be_left_blank(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $layer->mapType->dynamicAttributes()->create([
            'tipe' => 'custom', 'kode_atribut' => 'catatan', 'label' => 'Catatan', 'is_wajib' => false,
        ]);

        $this->actingAs($admin)->post(route('spatial-layers.features.store', $layer), [
            'geometry_wkt' => 'POINT(127.5 0.8)',
        ])->assertRedirect(route('spatial-layers.show', $layer));

        $this->assertSame(1, $layer->features()->count());
    }

    public function test_metadata_dinamis_stored_separately_from_imported_attributes(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $layer->mapType->dynamicAttributes()->create([
            'tipe' => 'placeholder', 'kode_atribut' => 'pagu', 'label' => 'Pagu', 'is_wajib' => true,
        ]);
        $feature = SpatialLayerFeature::create([
            'spatial_layer_id' => $layer->id,
            'geometry' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)'),
            'attributes' => ['KODE_ASLI' => 'ABC123'],
        ]);

        $this->actingAs($admin)->put(route('spatial-layers.features.update', [$layer, $feature]), [
            'geometry_wkt' => 'POINT(127.5 0.8)',
            'metadata_dinamis' => ['pagu' => '5000000'],
        ])->assertRedirect(route('spatial-layers.show', $layer));

        $feature->refresh();
        $this->assertSame(['pagu' => '5000000'], $feature->metadata_dinamis);
        $this->assertSame(['KODE_ASLI' => 'ABC123'], $feature->attributes);
    }

    public function test_jenis_without_active_dynamic_attributes_requires_no_extra_field(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();

        $this->actingAs($admin)->post(route('spatial-layers.features.store', $layer), [
            'geometry_wkt' => 'POINT(127.5 0.8)',
        ])->assertRedirect(route('spatial-layers.show', $layer));

        $this->assertSame(1, $layer->features()->count());
    }

    /**
     * Regresi: halaman ini perlu benar-benar dirender (GET), bukan cuma dites lewat
     * store()/update() — pola bug yang sama (kesalahan kompilasi Blade lolos dari
     * test) sempat terjadi di map-types/_form.blade.php.
     */
    public function test_create_page_renders(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $layer->mapType->dynamicAttributes()->create(['tipe' => 'custom', 'kode_atribut' => 'catatan', 'label' => 'Catatan']);

        $this->actingAs($admin)->get(route('spatial-layers.features.create', $layer))->assertOk();
    }

    public function test_edit_page_renders_with_imported_attributes_and_metadata_dinamis(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $layer->mapType->dynamicAttributes()->create(['tipe' => 'placeholder', 'kode_atribut' => 'pagu', 'label' => 'Pagu']);
        $feature = SpatialLayerFeature::create([
            'spatial_layer_id' => $layer->id,
            'geometry' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)'),
            'attributes' => ['KODE_ASLI' => 'ABC123'],
            'metadata_dinamis' => ['pagu' => '5000000'],
        ]);

        $this->actingAs($admin)->get(route('spatial-layers.features.edit', [$layer, $feature]))
            ->assertOk()
            ->assertSee('KODE_ASLI')
            ->assertSee('POINT');
    }
}
