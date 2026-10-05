<?php

namespace Tests\Feature;

use App\Models\Sector;
use App\Models\SpatialLayer;
use App\Models\SpatialLayerFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regresi model SpatialLayerFeature — skema v3 (plan mellow-weaving-eclipse
 * Fase 3). Test lama untuk relasi layer->sector() dan intervensi antar-fitur
 * (SpatialLayerFeatureIntervention) DIHAPUS di sini:
 * - `sector_id`/`sector()`: kolom ini tidak ada lagi di layers_v3 (dokumen
 *   v3 §5.4 tidak punya kolom sektor).
 * - Intervensi antar-fitur: tabel `spatial_layer_feature_interventions` FK
 *   ke `spatial_layer_features` (v2) lama, belum punya rekan v3 (plan Fase 7
 *   catatan retirement) — dan relasinya sudah dihapus dari model
 *   SpatialLayerFeature karena sebelumnya terbukti dead code (tidak dipakai
 *   controller/view mana pun selain test ini sendiri).
 */
class SpatialLayerFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_sectors_are_seeded_from_migration(): void
    {
        $this->assertSame(5, Sector::count());
        $this->assertTrue(Sector::where('code', 'pupr')->exists());
    }

    public function test_feature_belongs_to_layer_and_casts_properties_as_array(): void
    {
        $categoryId = DB::table('categories_v3')->insertGetId([
            'id' => (string) Str::uuid(),
            'name' => 'Kategori Uji',
            'slug' => 'kategori-uji-'.Str::random(6),
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id');

        $layer = SpatialLayer::create([
            'category_id' => $categoryId,
            'layer_type_id' => 4,
            'code' => 'layer-jalan-fitur',
            'slug' => 'jalan-fitur',
            'name' => 'Jalan',
        ]);

        $feature = SpatialLayerFeature::create([
            'layer_id' => $layer->id,
            'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)'),
            'properties' => ['kondisi' => 'baik'],
        ]);

        $this->assertTrue($feature->fresh()->layer->is($layer));
        $this->assertSame(['kondisi' => 'baik'], $feature->fresh()->properties);
    }
}
