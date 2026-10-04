<?php

namespace Tests\Feature;

use App\Models\LayerSource;
use App\Models\Opd;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SpatialLayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Fase E (spec-admin-manajemen-peta.md §5.4) — Source layanan eksternal per
 * Layer. Layer bertipe service/raster (`layer_types.stores_features = false`)
 * dirender langsung dari sini di peta publik, tanpa `spatial_features`.
 */
class LayerSourceControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'description' => null]);
        foreach (['spatial-layers.view', 'spatial-layers.create', 'spatial-layers.edit', 'spatial-layers.delete', 'spatial-layers.publish'] as $perm) {
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
        return SpatialLayer::create(array_merge([
            'category_id' => $this->categoryId(),
            'layer_type_id' => 4,
            'code' => 'layer-'.Str::random(8),
            'slug' => 'layer-'.Str::random(8),
            'name' => 'Layer Uji',
            'status' => 'draft',
        ], $overrides));
    }

    private function sourcePayload(array $overrides = []): array
    {
        return array_merge([
            'source_type' => 'wms',
            'name' => 'Peta Dasar BIG',
            'url' => 'https://contoh.big.go.id/wms',
            'service_layer_name' => 'rupabumi',
            'format' => 'image/png',
            'crs' => 'EPSG:4326',
            'auth_type' => 'none',
        ], $overrides);
    }

    public function test_index_page_renders(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();

        $this->actingAs($admin)->get(route('spatial-layers.sources.index', $layer))->assertOk();
    }

    public function test_admin_can_create_a_source(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();

        $this->actingAs($admin)->post(route('spatial-layers.sources.store', $layer), $this->sourcePayload())
            ->assertRedirect(route('spatial-layers.sources.index', $layer));

        $this->assertDatabaseHas('layer_sources', [
            'layer_id' => $layer->id,
            'source_type' => 'wms',
            'url' => 'https://contoh.big.go.id/wms',
        ]);
    }

    public function test_url_is_required(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();

        $this->actingAs($admin)->post(route('spatial-layers.sources.store', $layer), $this->sourcePayload(['url' => '']))
            ->assertSessionHasErrors('url');
    }

    public function test_database_and_file_upload_source_types_are_not_offered(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();

        $this->actingAs($admin)->post(route('spatial-layers.sources.store', $layer), $this->sourcePayload(['source_type' => 'database']))
            ->assertSessionHasErrors('source_type');
    }

    /**
     * Partial unique index `uq_layer_sources_primary` menjaga hanya satu
     * Source primary per layer — dijaga lewat auto-demote (bukan exception),
     * konsisten dengan pola `is_default` di LayerStyle.
     */
    public function test_setting_a_new_primary_source_demotes_the_previous_one(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();

        $this->actingAs($admin)->post(route('spatial-layers.sources.store', $layer), $this->sourcePayload(['name' => 'Source A', 'is_primary' => '1']));
        $sourceA = LayerSource::where('name', 'Source A')->firstOrFail();

        $this->actingAs($admin)->post(route('spatial-layers.sources.store', $layer), $this->sourcePayload(['name' => 'Source B', 'is_primary' => '1']));
        $sourceB = LayerSource::where('name', 'Source B')->firstOrFail();

        $this->assertFalse($sourceA->fresh()->is_primary);
        $this->assertTrue($sourceB->fresh()->is_primary);
        $this->assertSame(1, LayerSource::where('layer_id', $layer->id)->where('is_primary', true)->count());
    }

    public function test_admin_can_update_a_source(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $source = LayerSource::create(array_merge(['layer_id' => $layer->id], $this->sourcePayload()));

        $this->actingAs($admin)->put(route('spatial-layers.sources.update', [$layer, $source]), $this->sourcePayload(['name' => 'Nama Baru']))
            ->assertRedirect(route('spatial-layers.sources.index', $layer));

        $this->assertSame('Nama Baru', $source->fresh()->name);
    }

    public function test_admin_can_delete_a_source(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $source = LayerSource::create(array_merge(['layer_id' => $layer->id], $this->sourcePayload()));

        $this->actingAs($admin)->delete(route('spatial-layers.sources.destroy', [$layer, $source]))
            ->assertRedirect(route('spatial-layers.sources.index', $layer));

        $this->assertDatabaseMissing('layer_sources', ['id' => $source->id]);
    }

    public function test_test_connection_marks_health_ok_on_success(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);
        $admin = $this->admin();
        $layer = $this->layer();
        $source = LayerSource::create(array_merge(['layer_id' => $layer->id], $this->sourcePayload()));

        $this->actingAs($admin)->post(route('spatial-layers.sources.test-connection', [$layer, $source]))
            ->assertRedirect(route('spatial-layers.sources.index', $layer));

        $source->refresh();
        $this->assertSame('ok', $source->health_status);
        $this->assertNotNull($source->last_checked_at);
    }

    public function test_test_connection_marks_health_error_on_failure(): void
    {
        Http::fake(['*' => Http::response('', 500)]);
        $admin = $this->admin();
        $layer = $this->layer();
        $source = LayerSource::create(array_merge(['layer_id' => $layer->id], $this->sourcePayload()));

        $this->actingAs($admin)->post(route('spatial-layers.sources.test-connection', [$layer, $source]));

        $this->assertSame('error', $source->fresh()->health_status);
    }

    /**
     * R9: layer raster/service (stores_features = false) butuh Source primary
     * sebelum dipublikasikan — bukan fitur/style seperti layer vektor (R8).
     */
    public function test_publish_rejected_for_service_layer_without_primary_source(): void
    {
        $admin = $this->admin();
        $layer = $this->layer(['layer_type_id' => 20]);

        $this->actingAs($admin)->patch(route('spatial-layers.update-status', $layer), ['status' => 'published'])
            ->assertSessionHasErrors('status');

        $this->assertSame('draft', $layer->fresh()->status);
    }

    public function test_publish_allowed_for_service_layer_with_primary_source(): void
    {
        $admin = $this->admin();
        $layer = $this->layer(['layer_type_id' => 20]);
        LayerSource::create(array_merge(['layer_id' => $layer->id], $this->sourcePayload(['is_primary' => true])));

        $this->actingAs($admin)->patch(route('spatial-layers.update-status', $layer), ['status' => 'published'])
            ->assertRedirect(route('spatial-layers.show', $layer));

        $this->assertSame('published', $layer->fresh()->status);
    }

    public function test_admin_opd_cannot_manage_sources_of_another_opd_layer(): void
    {
        $opd = Opd::create(['name' => 'Dinas Uji', 'singkatan' => Str::upper(Str::random(5))]);
        $otherOpd = Opd::create(['name' => 'Dinas Lain', 'singkatan' => Str::upper(Str::random(5))]);
        $role = Role::create(['name' => 'Admin OPD', 'slug' => 'admin-opd', 'description' => null]);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'spatial-layers.view', 'guard_name' => 'web']));
        $user = User::factory()->create(['role_id' => $role->id, 'opd_id' => $opd->id]);
        $layer = $this->layer(['opd_id' => $otherOpd->id]);

        $this->actingAs($user)->get(route('spatial-layers.sources.index', $layer))->assertForbidden();
    }
}
