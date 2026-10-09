<?php

namespace Tests\Feature;

use App\Models\SpatialLayer;
use App\Models\SpatialLayerMetadata;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regresi model SpatialLayer — skema v3 (plan mellow-weaving-eclipse Fase 3).
 * Test lama untuk `is_group`/`selectable()`/`groups()` scope, relasi
 * parent()/children() antar-layer, dan atribut_schema/atributValidationRules()
 * DIHAPUS di sini — bukan sembarangan, melainkan karena kolom & mekanismenya
 * sudah tidak ada lagi secara sengaja di layers_v3 (hirarki pindah ke
 * categories_v3/category_nodes).
 *
 * `test_spatial_layer_belongs_to_map_type()` dihapus 2026-10-06 bersama kolom
 * `layers.map_type_id` (migration
 * drop_map_type_id_and_visibility_from_layers_table), dan
 * `test_map_types_are_seeded_from_migration()` dihapus 2026-10-10 bersama
 * seluruh modul "Jenis Peta" (migration drop_map_types_tables).
 */
class SpatialLayerTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_spatial_layer_metadata_is_one_to_one(): void
    {
        $layer = SpatialLayer::create([
            'category_id' => $this->categoryId(),
            'layer_type_id' => 4,
            'code' => 'layer-jalan-meta',
            'slug' => 'jalan-meta',
            'name' => 'Jalan Meta',
        ]);

        SpatialLayerMetadata::create([
            'layer_id' => $layer->id,
            'producer_organization' => 'Dinas PUPR',
            'data_year' => 2026,
        ]);

        $this->assertSame('Dinas PUPR', $layer->fresh()->metadata->producer_organization);
    }
}
