<?php

namespace Tests\Feature;

use App\Models\MapType;
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
 * Fase A (plan mellow-weaving-eclipse, implementasi spesifikasi admin
 * manajemen peta) — fondasi kewenangan OPD atas Layer (D15, D16, R17, R19–R21):
 * hanya super-admin/admin-bappeda boleh ubah status (publish/unpublish/arsip)
 * lewat permission `spatial-layers.publish`, dan admin-opd dibatasi hanya ke
 * Layer dengan `opd_id` miliknya sendiri.
 */
class SpatialLayerAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function roleWithPermissions(string $slug, array $permissions): Role
    {
        $role = Role::create(['name' => $slug, 'slug' => $slug, 'description' => null]);

        foreach ($permissions as $permission) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
        }

        return $role;
    }

    private function adminBappeda(): User
    {
        $role = $this->roleWithPermissions('admin-bappeda', [
            'spatial-layers.view', 'spatial-layers.create', 'spatial-layers.edit', 'spatial-layers.delete', 'spatial-layers.publish',
        ]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function adminOpd(Opd $opd): User
    {
        $role = Role::where('slug', 'admin-opd')->first() ?? $this->roleWithPermissions('admin-opd', [
            'spatial-layers.view', 'spatial-layers.create', 'spatial-layers.edit', 'spatial-layers.delete',
        ]);

        if (! $role->hasPermissionTo('spatial-layers.view')) {
            foreach (['spatial-layers.view', 'spatial-layers.create', 'spatial-layers.edit', 'spatial-layers.delete'] as $permission) {
                $role->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
            }
        }

        return User::factory()->create(['role_id' => $role->id, 'opd_id' => $opd->id]);
    }

    private function opd(string $name = 'Dinas Uji'): Opd
    {
        return Opd::create(['name' => $name, 'singkatan' => Str::upper(Str::random(5))]);
    }

    private function jenis(): MapType
    {
        return MapType::where('slug', 'tematik')->firstOrFail();
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

    private function createLayer(array $attributes = []): SpatialLayer
    {
        return SpatialLayer::create(array_merge([
            'category_id' => $this->categoryId(),
            'layer_type_id' => 4,
            'code' => 'layer-'.Str::random(8),
            'slug' => 'layer-'.Str::random(8),
            'name' => 'Layer Uji',
            'status' => 'draft',
        ], $attributes));
    }

    public function test_admin_opd_created_layer_is_forced_to_draft_with_own_opd(): void
    {
        $opd = $this->opd();
        $user = $this->adminOpd($opd);
        $otherOpd = $this->opd('Dinas Lain');
        $jenis = $this->jenis();
        $categoryId = $this->categoryId();

        $this->actingAs($user)->post(route('spatial-layers.store'), [
            'map_type_id' => $jenis->id,
            'layer_type_id' => 4,
            'category_id' => $categoryId,
            'opd_id' => $otherOpd->id,
            'is_active' => '1',
            'name' => 'Layer OPD Uji',
        ]);

        $this->assertDatabaseHas('layers', [
            'name' => 'Layer OPD Uji',
            'opd_id' => $opd->id,
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    public function test_admin_opd_cannot_view_layer_owned_by_another_opd(): void
    {
        $opd = $this->opd();
        $user = $this->adminOpd($opd);
        $otherOpd = $this->opd('Dinas Lain');
        $layer = $this->createLayer(['opd_id' => $otherOpd->id]);

        $this->actingAs($user)->get(route('spatial-layers.show', $layer))->assertForbidden();
    }

    public function test_admin_opd_cannot_view_layer_with_no_opd(): void
    {
        $opd = $this->opd();
        $user = $this->adminOpd($opd);
        $layer = $this->createLayer(['opd_id' => null]);

        $this->actingAs($user)->get(route('spatial-layers.show', $layer))->assertForbidden();
    }

    public function test_admin_opd_index_only_lists_own_opd_layers(): void
    {
        $opd = $this->opd();
        $user = $this->adminOpd($opd);
        $otherOpd = $this->opd('Dinas Lain');

        $this->createLayer(['name' => 'Layer Milik Saya', 'opd_id' => $opd->id]);
        $this->createLayer(['name' => 'Layer Milik Lain', 'opd_id' => $otherOpd->id]);

        $response = $this->actingAs($user)->get(route('spatial-layers.index'));

        $response->assertOk()->assertSee('Layer Milik Saya')->assertDontSee('Layer Milik Lain');
    }

    public function test_admin_opd_cannot_change_layer_status(): void
    {
        $opd = $this->opd();
        $user = $this->adminOpd($opd);
        $layer = $this->createLayer(['opd_id' => $opd->id]);

        $this->actingAs($user)->patch(route('spatial-layers.update-status', $layer), [
            'status' => 'published',
        ])->assertForbidden();

        $this->assertSame('draft', $layer->fresh()->status);
    }

    public function test_admin_bappeda_can_publish_layer_with_feature_and_default_style(): void
    {
        $admin = $this->adminBappeda();
        $layer = $this->createLayer();
        SpatialLayerFeature::create(['layer_id' => $layer->id, 'geom' => DB::raw("ST_GeomFromText('POINT(127.8 1.5)', 4326)")]);
        $style = $layer->styles()->create(['name' => 'Default', 'style_type' => 'simple', 'is_default' => true, 'definition' => []]);
        $layer->update(['default_style_id' => $style->id]);

        $this->actingAs($admin)->patch(route('spatial-layers.update-status', $layer), [
            'status' => 'published',
        ])->assertRedirect(route('spatial-layers.show', $layer));

        $layer->refresh();
        $this->assertSame('published', $layer->status);
        $this->assertNotNull($layer->published_at);
    }

    public function test_publish_rejected_when_layer_has_no_features(): void
    {
        $admin = $this->adminBappeda();
        $layer = $this->createLayer();
        $style = $layer->styles()->create(['name' => 'Default', 'style_type' => 'simple', 'is_default' => true, 'definition' => []]);
        $layer->update(['default_style_id' => $style->id]);

        $this->actingAs($admin)->patch(route('spatial-layers.update-status', $layer), [
            'status' => 'published',
        ])->assertSessionHasErrors('status');

        $this->assertSame('draft', $layer->fresh()->status);
    }

    public function test_publish_rejected_when_layer_has_no_default_style(): void
    {
        $admin = $this->adminBappeda();
        $layer = $this->createLayer();
        SpatialLayerFeature::create(['layer_id' => $layer->id, 'geom' => DB::raw("ST_GeomFromText('POINT(127.8 1.5)', 4326)")]);

        $this->actingAs($admin)->patch(route('spatial-layers.update-status', $layer), [
            'status' => 'published',
        ])->assertSessionHasErrors('status');

        $this->assertSame('draft', $layer->fresh()->status);
    }

    public function test_admin_bappeda_can_assign_and_move_opd_ownership(): void
    {
        $admin = $this->adminBappeda();
        $opd = $this->opd();
        $jenis = $this->jenis();
        $categoryId = $this->categoryId();

        $this->actingAs($admin)->post(route('spatial-layers.store'), [
            'map_type_id' => $jenis->id,
            'layer_type_id' => 4,
            'category_id' => $categoryId,
            'opd_id' => $opd->id,
            'name' => 'Layer Bappeda Uji',
        ]);

        $this->assertDatabaseHas('layers', ['name' => 'Layer Bappeda Uji', 'opd_id' => $opd->id]);

        $layer = SpatialLayer::where('name', 'Layer Bappeda Uji')->firstOrFail();
        $otherOpd = $this->opd('Dinas Lain');

        $this->actingAs($admin)->put(route('spatial-layers.update', $layer), [
            'map_type_id' => $jenis->id,
            'category_id' => $layer->category_id,
            'opd_id' => $otherOpd->id,
            'name' => 'Layer Bappeda Uji',
        ])->assertRedirect(route('spatial-layers.show', $layer));

        $this->assertSame($otherOpd->id, $layer->fresh()->opd_id);
    }
}
