<?php

namespace Tests\Feature;

use App\Models\DataSpatial;
use App\Models\LegacyCategory as Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regresi perbandingan output (plan mellow-weaving-eclipse, Fase 4, checkpoint
 * "perbandingan output geojson lama vs baru untuk sample kategori sebelum
 * cutover publik"). Sejak Fase 4, `/geojson` (FrontendController) SUDAH
 * membaca skema v3 (spatial_features_v3/layers_v3) — data yang ditulis admin
 * ke `data_spatial` lama (lewat DataSpatialController, belum direwrite, lihat
 * Fase 5) direplikasi real-time ke v3 oleh App\Support\SpatialFeaturesV3Sync
 * (dipasang di AppServiceProvider lewat event model DataSpatial).
 *
 * Test ini mengunci bahwa jembatan replikasi itu benar: menulis ke
 * data_spatial HARUS langsung muncul lewat kedua endpoint publik (/geojson
 * lama & /peta-v2/geojson/{slug} baru yang sejak awal sudah v3-native),
 * dengan geometri & properti yang konsisten.
 */
class SchemaV3GeojsonParityTest extends TestCase
{
    use RefreshDatabase;

    private function legacyCategoryId(array $overrides = []): int
    {
        return Category::create(array_merge([
            'type' => 'tematik',
            'nama' => 'Fasilitas Kesehatan',
            'warna' => '#ff0000',
            'icon' => 'hospital',
            'is_marker' => true,
            'is_active' => true,
        ], $overrides))->id;
    }

    private function legacyFeature(int $kategoriId, float $lon, float $lat, array $overrides = []): DataSpatial
    {
        return DataSpatial::create(array_merge([
            'data_type' => 'tematik',
            'kategori_id' => $kategoriId,
            'deskripsi' => 'Puskesmas Uji',
            'geom' => DB::raw("ST_SetSRID(ST_MakePoint({$lon}, {$lat}), 4326)"),
        ], $overrides));
    }

    private function v3LayerSlugFor(int $kategoriId): string
    {
        return DB::table('layers')->where('legacy_category_id', $kategoriId)->value('slug');
    }

    public function test_writing_to_legacy_data_spatial_is_immediately_visible_on_both_public_endpoints(): void
    {
        $kategoriId = $this->legacyCategoryId();
        $this->legacyFeature($kategoriId, 127.50, 0.80);
        $this->legacyFeature($kategoriId, 127.51, 0.81);
        $this->legacyFeature($kategoriId, 127.52, 0.82);

        $slug = $this->v3LayerSlugFor($kategoriId);

        $legacy = $this->getJson('/geojson?type=tematik')->assertOk();
        $v3 = $this->getJson("/peta-v2/geojson/{$slug}")->assertOk();

        $this->assertCount(3, $legacy->json('features'));
        $this->assertCount(3, $v3->json('features'));
    }

    public function test_feature_geometry_and_metadata_match_between_legacy_and_v3_endpoints(): void
    {
        $kategoriId = $this->legacyCategoryId();
        $this->legacyFeature($kategoriId, 127.123, 0.456, ['sumber_data' => 'Survei Uji']);

        $slug = $this->v3LayerSlugFor($kategoriId);

        $legacy = $this->getJson('/geojson?type=tematik')->assertOk();
        $v3 = $this->getJson("/peta-v2/geojson/{$slug}")->assertOk();

        $legacyCoords = $legacy->json('features.0.geometry.coordinates');
        $v3Coords = $v3->json('features.0.geometry.coordinates');

        $this->assertEqualsWithDelta(127.123, $legacyCoords[0], 0.0001);
        $this->assertEqualsWithDelta(0.456, $legacyCoords[1], 0.0001);
        $this->assertEqualsWithDelta($legacyCoords[0], $v3Coords[0], 0.0001);
        $this->assertEqualsWithDelta($legacyCoords[1], $v3Coords[1], 0.0001);

        $this->assertSame('Survei Uji', $legacy->json('features.0.properties.sumber_data'));
        $this->assertSame('Survei Uji', $v3->json('features.0.properties.attributes.sumber_data'));
    }

    public function test_deleting_legacy_data_spatial_row_removes_it_from_both_endpoints(): void
    {
        $kategoriId = $this->legacyCategoryId();
        $feature = $this->legacyFeature($kategoriId, 127.5, 0.8);
        $slug = $this->v3LayerSlugFor($kategoriId);

        $this->getJson('/geojson?type=tematik')->assertJsonCount(1, 'features');

        $feature->delete();

        $this->getJson('/geojson?type=tematik')->assertJsonCount(0, 'features');
        $this->getJson("/peta-v2/geojson/{$slug}")->assertJsonCount(0, 'features');
    }

    /**
     * Sejak FrontendController::getGeojsonByDataType() dibaca dari v3 (Fase 4),
     * filter `type` mengikuti tipe KATEGORI (categories_v3.type), bukan lagi
     * kolom data_spatial.data_type per baris — konsisten dengan arsitektur baru
     * di mana data_type/sub_type adalah atribut kategori, bukan atribut fitur
     * (Keputusan Desain Kunci #3). Jadi exclusion yang benar diuji lewat
     * kategori yang BERBEDA, bukan lewat data_type yang beda pada kategori
     * yang sama (kombinasi itu sendiri tidak pernah terjadi di data nyata).
     */
    public function test_legacy_endpoint_excludes_features_from_a_different_category_type(): void
    {
        $tematikId = $this->legacyCategoryId();
        $this->legacyFeature($tematikId, 127.5, 0.8);

        $musrenbangId = $this->legacyCategoryId(['type' => 'usulan_musrenbang', 'nama' => 'Kategori Musrenbang']);
        $this->legacyFeature($musrenbangId, 127.6, 0.9, ['data_type' => 'usulan_musrenbang']);

        $legacy = $this->getJson('/geojson?type=tematik')->assertOk();

        $this->assertCount(1, $legacy->json('features'));
    }
}
