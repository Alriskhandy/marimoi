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
 * opd_pengelola/tanggal_data, dan tidak kehilangan baris tanpa OPD) tapi lewat
 * jalur penulisan v3-native (modul admin baru, SpatialLayerFeatureController)
 * — satu-satunya jalur tulis yang tersisa sejak retirement, lihat
 * FrontendController::getGeojsonByDataType() yang membaca `sf.properties`
 * langsung (bukan lagi `ds.*` kolom legacy) untuk baris tanpa jejak legacy.
 */
class FrontendGeojsonV3MetadataTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'description' => null]);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'spatial-layers.create', 'guard_name' => 'web']));

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function layer(): SpatialLayer
    {
        $jenis = MapType::where('slug', 'tematik')->firstOrFail();
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
            'map_type_id' => $jenis->id,
            'code' => 'layer-'.Str::random(8),
            'slug' => 'layer-'.Str::random(8),
            'name' => 'Layer Uji',
        ]);
    }

    public function test_public_geojson_endpoint_exposes_dataset_metadata_for_v3_native_feature(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();

        $this->actingAs($admin)->post(route('spatial-layers.features.store', $layer), [
            'input_type' => 'coordinates',
            'coordinates' => [['latitude' => 0.8, 'longitude' => 127.5]],
            'metadata_dinamis' => [
                'sumber_data' => 'Survei lapangan 2025',
                'opd_penanggung_jawab' => 'Dinas Uji Coba',
                'tanggal_data' => '2025-06-01',
            ],
        ])->assertRedirect(route('spatial-layers.show', $layer));

        $featureId = $layer->features()->first()->id;

        $response = $this->getJson('/geojson?type=tematik');

        $response->assertOk();
        $feature = collect($response->json('features'))->firstWhere('properties.id', $featureId);

        $this->assertNotNull($feature, 'feature untuk data uji tidak ditemukan pada response geojson');
        $this->assertSame('Survei lapangan 2025', $feature['properties']['sumber_data']);
        $this->assertSame('Dinas Uji Coba', $feature['properties']['opd_pengelola']);
        $this->assertSame('01-06-2025', $feature['properties']['tanggal_data']);
    }

    /**
     * Modul baru mewajibkan opd_penanggung_jawab secara permanen (lihat
     * dokumentasi kelas) — admin-opd/admin-bappeda tidak bisa membuat fitur
     * tanpa field ini lewat UI. Tapi secara data, baris tanpa field itu tetap
     * bisa ada (mis. sisa impor lama), jadi FrontendController::
     * getGeojsonByDataType() tetap wajib LEFT JOIN (bukan JOIN) ke opd supaya
     * tidak menghilangkan baris seperti itu — diuji di sini lewat penulisan
     * model langsung (bypass validasi controller) untuk menyasar query SQL-nya.
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
