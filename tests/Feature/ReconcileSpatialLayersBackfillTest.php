<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DataSpatial;
use App\Models\MapType;
use App\Models\SpatialLayer;
use App\Models\SpatialLayerFeature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresi untuk marimoi:reconcile-spatial-layers-backfill (docs/marimoi v2/
 * 04_implementation/12-implementasi-perbaikan-pemetaan.md Bagian 1.6 langkah 2).
 * Dites TANPA Observer (belum ada di codebase ini) — mensimulasikan categories/
 * data_spatial yang dibuat lewat CategoryController/DataSpatialController TANPA
 * ada baris spatial_layers/spatial_layer_features yang berpadanan, persis kondisi
 * nyata "dibuat setelah backfill awal".
 */
class ReconcileSpatialLayersBackfillTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create();
    }

    public function test_new_category_without_matching_spatial_layer_gets_backfilled(): void
    {
        $category = Category::create(['type' => 'tematik', 'nama' => 'Kategori Baru Uji', 'warna' => '#123456', 'is_marker' => true, 'user_id' => $this->user()->id]);

        $this->artisan('marimoi:reconcile-spatial-layers-backfill')->assertExitCode(0);

        $layer = SpatialLayer::where('legacy_category_id', $category->id)->first();
        $this->assertNotNull($layer);
        $this->assertSame('Kategori Baru Uji', $layer->name);
        $this->assertSame('#123456', $layer->color);
        $this->assertTrue($layer->is_marker);
    }

    public function test_new_data_spatial_without_matching_feature_gets_backfilled(): void
    {
        $user = $this->user();
        $category = Category::create(['type' => 'tematik', 'nama' => 'Kategori Uji', 'warna' => '#000', 'user_id' => $user->id]);
        $ds = DataSpatial::factory()->create([
            'user_id' => $user->id,
            'kategori_id' => $category->id,
        ]);

        $this->artisan('marimoi:reconcile-spatial-layers-backfill');

        $feature = SpatialLayerFeature::where('legacy_data_spatial_id', $ds->id)->first();
        $this->assertNotNull($feature);
    }

    public function test_new_root_category_ends_up_with_its_own_granular_jenis(): void
    {
        $category = Category::create(['type' => 'tematik', 'nama' => 'Taman Kota Uji', 'warna' => '#000', 'user_id' => $this->user()->id]);

        $this->artisan('marimoi:reconcile-spatial-layers-backfill');

        $layer = SpatialLayer::where('legacy_category_id', $category->id)->firstOrFail();
        $jenis = MapType::find($layer->map_type_id);
        $this->assertSame('Taman Kota Uji', $jenis->nama);
    }

    public function test_child_category_of_already_granularized_root_inherits_parent_jenis(): void
    {
        $userId = $this->user()->id;
        $rootCategory = Category::create(['type' => 'tematik', 'nama' => 'Jalan Kota Uji', 'warna' => '#000', 'user_id' => $userId]);
        $this->artisan('marimoi:reconcile-spatial-layers-backfill');
        $rootJenis = MapType::where('nama', 'Jalan Kota Uji')->firstOrFail();

        $childCategory = Category::create(['type' => 'tematik', 'nama' => 'Jalan Kota Uji - Ruas B', 'warna' => '#000', 'parent_id' => $rootCategory->id, 'user_id' => $userId]);

        $this->artisan('marimoi:reconcile-spatial-layers-backfill');

        $childLayer = SpatialLayer::where('legacy_category_id', $childCategory->id)->firstOrFail();
        $this->assertSame($rootJenis->id, $childLayer->map_type_id);
        $this->assertFalse(MapType::where('nama', 'Jalan Kota Uji - Ruas B')->exists());
    }

    public function test_dry_run_does_not_persist_changes(): void
    {
        Category::create(['type' => 'tematik', 'nama' => 'Kategori Dry Run', 'warna' => '#000', 'user_id' => $this->user()->id]);

        $this->artisan('marimoi:reconcile-spatial-layers-backfill --dry-run');

        $this->assertFalse(SpatialLayer::where('name', 'Kategori Dry Run')->exists());
    }

    public function test_running_twice_does_not_duplicate_features(): void
    {
        $user = $this->user();
        $category = Category::create(['type' => 'tematik', 'nama' => 'Kategori Uji', 'warna' => '#000', 'user_id' => $user->id]);
        $ds = DataSpatial::factory()->create(['user_id' => $user->id, 'kategori_id' => $category->id]);

        $this->artisan('marimoi:reconcile-spatial-layers-backfill');
        $this->artisan('marimoi:reconcile-spatial-layers-backfill');

        $this->assertSame(1, SpatialLayerFeature::where('legacy_data_spatial_id', $ds->id)->count());
    }
}
