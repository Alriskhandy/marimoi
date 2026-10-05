<?php

namespace Tests\Feature;

use App\Models\SpatialLayer;
use App\Models\SpatialLayerFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pengganti `FrontendGeojsonMetadataTest` (dihapus Fase I/D13, plan
 * mellow-weaving-eclipse) — test lama menulis ke `data_spatial` legacy lewat
 * Eloquent langsung lalu mengandalkan `App\Support\SpatialFeaturesV3Sync`
 * (jembatan yang sudah dihapus bersama retirement modul Data Spasial lama)
 * untuk membuatnya muncul di `/geojson` publik.
 *
 * Test ini mengunci PERILAKU YANG SAMA (`/geojson` mengekspos sumber_data/
 * opd_pengelola/tanggal_data dari `sf.properties`, dan tidak kehilangan baris
 * tanpa OPD) — ditulis LANGSUNG lewat model (bukan lewat form admin) sejak
 * 2026-10-06: `layers.map_type_id` dihapus (lihat migration
 * drop_map_type_id_and_visibility_from_layers_table), jadi
 * `SpatialLayerFeatureController::store()` tidak lagi memvalidasi/menyimpan
 * `metadata_dinamis` sama sekali (tidak ada lagi atribut dinamis aktif untuk
 * Layer manapun) — jalur HTTP form bukan lagi cara yang valid untuk mengisi
 * field ini. Endpoint publik `/geojson` sendiri tidak terpengaruh: dia selalu
 * membaca `sf.properties` apa adanya, dari sumber mana pun field itu berasal.
 */
class FrontendGeojsonV3MetadataTest extends TestCase
{
    use RefreshDatabase;

    private function layer(): SpatialLayer
    {
        $categoryId = DB::table('categories_v3')->insertGetId([
            'id' => (string) Str::uuid(),
            'name' => 'Kategori Uji',
            'slug' => 'kategori-uji-'.Str::random(6),
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id');

        return SpatialLayer::create([
            'category_id' => $categoryId,
            'layer_type_id' => 4,
            'code' => 'layer-'.Str::random(8),
            'slug' => 'layer-'.Str::random(8),
            'name' => 'Layer Uji',
        ]);
    }

    public function test_public_geojson_endpoint_exposes_dataset_metadata_for_v3_native_feature(): void
    {
        $layer = $this->layer();
        $feature = SpatialLayerFeature::create([
            'layer_id' => $layer->id,
            'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)'),
            'properties' => [
                'sumber_data' => 'Survei lapangan 2025',
                'opd_penanggung_jawab' => 'Dinas Uji Coba',
                'tanggal_data' => '2025-06-01',
            ],
        ]);

        $response = $this->getJson('/geojson?type=tematik');

        $response->assertOk();
        $responseFeature = collect($response->json('features'))->firstWhere('properties.id', $feature->id);

        $this->assertNotNull($responseFeature, 'feature untuk data uji tidak ditemukan pada response geojson');
        $this->assertSame('Survei lapangan 2025', $responseFeature['properties']['sumber_data']);
        $this->assertSame('Dinas Uji Coba', $responseFeature['properties']['opd_pengelola']);
        $this->assertSame('01-06-2025', $responseFeature['properties']['tanggal_data']);
    }

    /**
     * `FrontendController::getGeojsonByDataType()` wajib LEFT JOIN (bukan JOIN)
     * ke opd supaya baris tanpa opd_penanggung_jawab (mis. sisa impor lama)
     * tidak hilang dari response.
     */
    public function test_public_geojson_endpoint_still_returns_v3_native_features_without_opd(): void
    {
        $layer = $this->layer();
        $feature = SpatialLayerFeature::create([
            'layer_id' => $layer->id,
            'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.6, 0.9), 4326)'),
            'properties' => ['sumber_data' => 'Survei Tanpa OPD', 'tanggal_data' => '2025-06-02'],
        ]);

        $response = $this->getJson('/geojson?type=tematik');

        $response->assertOk();
        $responseFeature = collect($response->json('features'))->firstWhere('properties.id', $feature->id);

        $this->assertNotNull($responseFeature, 'left join ke opd tidak boleh menghilangkan baris tanpa opd_penanggung_jawab');
        $this->assertNull($responseFeature['properties']['opd_pengelola']);
    }
}
