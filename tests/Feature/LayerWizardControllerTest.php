<?php

namespace Tests\Feature;

use App\Models\Opd;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SpatialLayer;
use App\Models\SpatialLayerFeature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Wizard "Tambah Layer" 4 tahap (2026-10-06, plan rippling-frolicking-ladybug):
 * Informasi Layer -> Impor Data -> Mapping Atribut -> Review & Publish.
 * Checkpoint-nya adalah `layers.wizard_step` — fokus test ini membuktikan
 * requirement utama: kegagalan parsing di tahap impor TIDAK memaksa Layer
 * (dan Informasi Layer yang sudah disimpan) dibuat ulang dari awal.
 */
class LayerWizardControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'description' => null]);
        foreach (['spatial-layers.view', 'spatial-layers.create'] as $perm) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']));
        }

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function publisher(): User
    {
        $role = Role::create(['name' => 'Publisher Uji', 'slug' => 'publisher-uji', 'description' => null]);
        foreach (['spatial-layers.view', 'spatial-layers.create', 'spatial-layers.publish'] as $perm) {
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

    private function kmlUploadedFile(string $placemarkName = 'Titik Uji', bool $valid = true): UploadedFile
    {
        $placemarks = $valid
            ? "<Placemark><name>{$placemarkName}</name><Point><coordinates>127.5,0.8,0</coordinates></Point></Placemark>"
            : '';

        $kml = <<<KML
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Document>
    {$placemarks}
  </Document>
</kml>
KML;

        $path = tempnam(sys_get_temp_dir(), 'kml').'.kml';
        file_put_contents($path, $kml);

        return new UploadedFile($path, 'lokasi.kml', 'application/vnd.google-earth.kml+xml', null, true);
    }

    /**
     * Jalankan tahap 1 (store) sampai berhasil, kembalikan Layer draft yang
     * dihasilkan (wizard_step = 2).
     */
    private function createDraftLayer(User $user, array $overrides = []): SpatialLayer
    {
        $categoryId = $this->categoryId();

        $this->actingAs($user)->post(route('spatial-layers.store'), array_merge([
            'category_id' => $categoryId,
            'layer_type_id' => 4,
            'name' => 'Layer Wizard Uji',
        ], $overrides));

        return SpatialLayer::where('category_id', $categoryId)->firstOrFail();
    }

    public function test_store_creates_draft_layer_with_default_style_and_wizard_step_2(): void
    {
        $admin = $this->admin();
        $categoryId = $this->categoryId();

        $response = $this->actingAs($admin)->post(route('spatial-layers.store'), [
            'category_id' => $categoryId,
            'layer_type_id' => 4,
            'name' => 'Layer Baru',
            'color' => '#ff0000',
        ]);

        $layer = SpatialLayer::where('name', 'Layer Baru')->firstOrFail();

        $response->assertRedirect(route('spatial-layers.wizard', $layer));
        $this->assertSame('draft', $layer->status);
        $this->assertSame(2, $layer->wizard_step);
        $this->assertNotNull($layer->default_style_id);
        $this->assertDatabaseHas('layer_styles', [
            'layer_id' => $layer->id,
            'is_default' => true,
        ]);
    }

    public function test_store_never_publishes_even_with_publish_permission(): void
    {
        $publisher = $this->publisher();
        $categoryId = $this->categoryId();

        $this->actingAs($publisher)->post(route('spatial-layers.store'), [
            'category_id' => $categoryId,
            'layer_type_id' => 4,
            'name' => 'Layer Tidak Boleh Publish',
        ]);

        $layer = SpatialLayer::where('name', 'Layer Tidak Boleh Publish')->firstOrFail();

        $this->assertSame('draft', $layer->status);
    }

    public function test_layer_type_dropdown_excludes_non_vector_types(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('spatial-layers.create'))
            ->assertOk()
            ->assertDontSee('Layanan WMS')
            ->assertSee('Vektor Titik');
    }

    public function test_failed_import_keeps_wizard_step_and_draft_layer_intact(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $layer = $this->createDraftLayer($admin);

        $response = $this->actingAs($admin)->post(route('spatial-layers.wizard.import', $layer), [
            'input_type' => 'kmz',
            'kmz_file' => $this->kmlUploadedFile(valid: false),
        ]);

        $layer->refresh();

        $response->assertRedirect(route('spatial-layers.wizard', $layer).'?step=2');
        $response->assertSessionHasErrors('input_type');
        $this->assertSame(2, $layer->wizard_step, 'wizard_step tidak boleh berubah saat impor gagal');
        $this->assertDatabaseHas('layer_imports', ['layer_id' => $layer->id, 'status' => 'failed']);
        $this->assertSame(0, $layer->features()->count());

        // Mengunggah ulang file yang valid langsung berhasil tanpa Layer kedua.
        $retry = $this->actingAs($admin)->post(route('spatial-layers.wizard.import', $layer), [
            'input_type' => 'kmz',
            'kmz_file' => $this->kmlUploadedFile(valid: true),
        ]);

        $layer->refresh();

        $retry->assertRedirect(route('spatial-layers.wizard', $layer));
        $this->assertSame(3, $layer->wizard_step);
        $this->assertSame(1, SpatialLayer::where('name', $layer->name)->count());
    }

    public function test_successful_import_advances_to_mapping_stage_without_saving_features_yet(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $layer = $this->createDraftLayer($admin);

        $this->actingAs($admin)->post(route('spatial-layers.wizard.import', $layer), [
            'input_type' => 'kmz',
            'kmz_file' => $this->kmlUploadedFile(),
        ]);

        $layer->refresh();

        $this->assertSame(3, $layer->wizard_step);
        $this->assertDatabaseHas('layer_imports', ['layer_id' => $layer->id, 'status' => 'mapping']);
        $this->assertSame(0, $layer->features()->count());
    }

    public function test_resuming_wizard_at_mapping_stage_renders_detected_fields(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $layer = $this->createDraftLayer($admin);

        $this->actingAs($admin)->post(route('spatial-layers.wizard.import', $layer), [
            'input_type' => 'kmz',
            'kmz_file' => $this->kmlUploadedFile('Titik Resume Uji'),
        ]);

        $this->actingAs($admin)->get(route('spatial-layers.wizard', $layer))
            ->assertOk()
            ->assertSee('NAMA', false);
    }

    public function test_processing_mapping_saves_features_and_advances_to_review_stage(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $layer = $this->createDraftLayer($admin);

        $this->actingAs($admin)->post(route('spatial-layers.wizard.import', $layer), [
            'input_type' => 'kmz',
            'kmz_file' => $this->kmlUploadedFile(),
        ]);

        $response = $this->actingAs($admin)->post(route('spatial-layers.wizard.mapping', $layer), [
            'mapping' => [],
        ]);

        $layer->refresh();

        $response->assertRedirect(route('spatial-layers.wizard', $layer));
        $this->assertSame(4, $layer->wizard_step);
        $this->assertSame(1, $layer->features()->count());
        $this->assertDatabaseHas('layer_imports', ['layer_id' => $layer->id, 'status' => 'completed']);
    }

    public function test_finish_without_publish_keeps_layer_as_draft_and_clears_wizard_step(): void
    {
        $admin = $this->admin();
        $layer = $this->createDraftLayer($admin);
        $layer->update(['wizard_step' => 4]);

        $response = $this->actingAs($admin)->post(route('spatial-layers.wizard.finish', $layer), [
            'publish' => '0',
        ]);

        $layer->refresh();

        $response->assertRedirect(route('spatial-layers.show', $layer));
        $this->assertNull($layer->wizard_step);
        $this->assertSame('draft', $layer->status);
    }

    public function test_finish_with_publish_by_authorized_user_publishes_layer(): void
    {
        $publisher = $this->publisher();
        $layer = $this->createDraftLayer($publisher);
        $layer->update(['wizard_step' => 4]);
        SpatialLayerFeature::create([
            'layer_id' => $layer->id,
            'geom' => DB::raw("ST_GeomFromText('POINT(127.5 0.8)', 4326)"),
        ]);

        $response = $this->actingAs($publisher)->post(route('spatial-layers.wizard.finish', $layer), [
            'publish' => '1',
        ]);

        $layer->refresh();

        $response->assertRedirect(route('spatial-layers.show', $layer));
        $this->assertNull($layer->wizard_step);
        $this->assertSame('published', $layer->status);
        $this->assertNotNull($layer->published_at);
    }

    public function test_finish_with_publish_by_unauthorized_user_stays_draft(): void
    {
        // Role tanpa `spatial-layers.publish` (bukan super-admin — super-admin
        // selalu lolos semua permission lewat Gate::before, lihat
        // AppServiceProvider) supaya pengecekan $canPublish benar-benar diuji.
        $role = Role::create(['name' => 'Editor Layer', 'slug' => 'editor-layer', 'description' => null]);
        foreach (['spatial-layers.view', 'spatial-layers.create'] as $perm) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']));
        }
        $editor = User::factory()->create(['role_id' => $role->id]);

        $layer = $this->createDraftLayer($editor);
        $layer->update(['wizard_step' => 4]);
        SpatialLayerFeature::create([
            'layer_id' => $layer->id,
            'geom' => DB::raw("ST_GeomFromText('POINT(127.5 0.8)', 4326)"),
        ]);

        $this->actingAs($editor)->post(route('spatial-layers.wizard.finish', $layer), [
            'publish' => '1',
        ]);

        $layer->refresh();

        $this->assertSame('draft', $layer->status);
        $this->assertNull($layer->wizard_step);
    }

    public function test_cannot_skip_ahead_to_a_step_beyond_wizard_step(): void
    {
        $admin = $this->admin();
        $layer = $this->createDraftLayer($admin);

        $response = $this->actingAs($admin)->get(route('spatial-layers.wizard', $layer).'?step=4');

        $response->assertOk()->assertSee('Impor Data Spasial', false);
    }

    public function test_admin_opd_cannot_access_wizard_of_another_opd_layer(): void
    {
        $opdA = Opd::create(['name' => 'OPD A', 'singkatan' => 'OPDA']);
        $opdB = Opd::create(['name' => 'OPD B', 'singkatan' => 'OPDB']);

        $role = Role::create(['name' => 'Admin OPD', 'slug' => 'admin-opd', 'description' => null]);
        foreach (['spatial-layers.view', 'spatial-layers.create'] as $perm) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']));
        }
        $adminOpd = User::factory()->create(['role_id' => $role->id, 'opd_id' => $opdA->id]);

        $layer = SpatialLayer::create([
            'category_id' => $this->categoryId(),
            'layer_type_id' => 4,
            'code' => 'layer-'.Str::random(8),
            'slug' => 'layer-'.Str::random(8),
            'name' => 'Layer OPD B',
            'opd_id' => $opdB->id,
            'wizard_step' => 2,
        ]);

        $this->actingAs($adminOpd)->get(route('spatial-layers.wizard', $layer))->assertForbidden();
    }

    public function test_index_shows_draft_banner_and_resume_button_for_wizard_drafts(): void
    {
        $admin = $this->admin();
        $layer = $this->createDraftLayer($admin, ['name' => 'Draft Belum Selesai']);

        $this->actingAs($admin)->get(route('spatial-layers.index'))
            ->assertOk()
            ->assertSee('belum selesai dibuat', false)
            ->assertSee(route('spatial-layers.wizard', $layer), false)
            ->assertSee('Tahap 2/4', false);
    }
}
