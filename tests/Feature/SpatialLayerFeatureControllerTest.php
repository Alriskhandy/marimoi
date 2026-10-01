<?php

namespace Tests\Feature;

use App\Models\MapType;
use App\Models\MetadataDefinition;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SpatialLayer;
use App\Models\SpatialLayerFeature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regresi untuk SpatialLayerFeatureController (docs/marimoi v2/04_implementation/
 * 12-implementasi-perbaikan-pemetaan.md Bagian 3.2) — attributes (impor) vs
 * metadata_dinamis (terstruktur) tersimpan terpisah, is_wajib divalidasi.
 * MapTypeDynamicAttribute sekarang pivot ke katalog global MetadataDefinition
 * (docs/marimoi v2/03_plan/14-penyesuaian-database-jenis-peta.md Bagian 6, Opsi B).
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
            'map_type_id' => $jenis->id,
        ], $overrides));
    }

    private function attachDefinition(SpatialLayer $layer, string $kode, array $definitionOverrides = [], array $pivotOverrides = []): void
    {
        $definition = MetadataDefinition::firstOrCreate(
            ['kode' => $kode],
            array_merge(['label' => ucfirst($kode)], $definitionOverrides)
        );

        $layer->mapType->dynamicAttributes()->create(array_merge(
            ['metadata_definition_id' => $definition->id],
            $pivotOverrides
        ));
    }

    /**
     * sumber_data/opd_penanggung_jawab/tanggal_data dipasang otomatis & wajib ke
     * SETIAP Jenis Peta (lihat MapTypeController::syncCoreAttributes(), migrasi
     * move_map_type_core_attributes_to_metadata_definitions) — jadi tiap kali
     * membuat/mengubah Data Spasial, 3 field metadata_dinamis ini wajib diisi
     * terlepas dari atribut tambahan apa pun yang dikonfigurasi di Jenisnya.
     *
     * @return array<string, string>
     */
    private function coreMetadataDinamis(): array
    {
        return [
            'sumber_data' => 'Dinas Uji',
            'opd_penanggung_jawab' => 'Dinas Uji',
            'tanggal_data' => '2026-01-01',
        ];
    }

    /**
     * store() sekarang berbasis `input_type` (shapefile/coordinates/kmz) — bukan
     * `geometry_wkt` langsung lagi (itu tersisa hanya di update()), disamakan
     * dengan wizard data-spatial/create lama. "coordinates" dengan 1 baris adalah
     * padanan paling sederhana dari dulu kirim 1 geometry_wkt POINT langsung.
     *
     * @return array<string, mixed>
     */
    private function coordinatesPayload(array $overrides = []): array
    {
        return array_merge([
            'input_type' => 'coordinates',
            'coordinates' => [
                ['latitude' => 0.8, 'longitude' => 127.5],
            ],
        ], $overrides);
    }

    public function test_admin_can_create_a_feature_with_coordinates_input(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();

        $this->actingAs($admin)->post(route('spatial-layers.features.store', $layer), $this->coordinatesPayload([
            'metadata_dinamis' => $this->coreMetadataDinamis(),
        ]))->assertRedirect(route('spatial-layers.show', $layer));

        $this->assertSame(1, $layer->features()->count());
    }

    public function test_coordinates_input_can_create_multiple_features_in_one_submit(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();

        $this->actingAs($admin)->post(route('spatial-layers.features.store', $layer), $this->coordinatesPayload([
            'coordinates' => [
                ['name' => 'Titik 1', 'latitude' => 0.8, 'longitude' => 127.5],
                ['name' => 'Titik 2', 'latitude' => 0.9, 'longitude' => 127.6],
            ],
            'metadata_dinamis' => $this->coreMetadataDinamis(),
        ]))->assertRedirect(route('spatial-layers.show', $layer));

        $this->assertSame(2, $layer->features()->count());
        $this->assertEqualsCanonicalizing(
            $this->coreMetadataDinamis(),
            $layer->features()->first()->metadata_dinamis
        );
    }

    /**
     * Regresi end-to-end jalur KMZ lewat HTTP — parsing KML itu sendiri sudah
     * diuji detail di tests/Unit/SpatialGeometryBatchImporterTest.php, di sini
     * cuma pastikan wiring controller (upload file, validasi, redirect) benar.
     */
    public function test_admin_can_create_features_from_kml_upload(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();

        $kml = <<<'KML'
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Document>
    <Placemark>
      <name>Titik KML Uji</name>
      <Point><coordinates>127.5,0.8,0</coordinates></Point>
    </Placemark>
  </Document>
</kml>
KML;

        $path = tempnam(sys_get_temp_dir(), 'kml').'.kml';
        file_put_contents($path, $kml);
        $file = new UploadedFile($path, 'lokasi.kml', 'application/vnd.google-earth.kml+xml', null, true);

        $this->actingAs($admin)->post(route('spatial-layers.features.store', $layer), [
            'input_type' => 'kmz',
            'kmz_file' => $file,
            'metadata_dinamis' => $this->coreMetadataDinamis(),
        ])->assertRedirect(route('spatial-layers.show', $layer));

        $this->assertSame(1, $layer->features()->count());
        $this->assertSame('Titik KML Uji', $layer->features()->first()->attributes['NAMA']);

        unlink($path);
    }

    public function test_store_requires_input_type(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();

        $this->actingAs($admin)->post(route('spatial-layers.features.store', $layer), [
            'metadata_dinamis' => $this->coreMetadataDinamis(),
        ])->assertSessionHasErrors('input_type');
    }

    public function test_required_dynamic_attribute_is_enforced(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $this->attachDefinition($layer, 'pagu', [], ['is_wajib' => true]);

        $this->actingAs($admin)->post(route('spatial-layers.features.store', $layer), $this->coordinatesPayload())
            ->assertSessionHasErrors('metadata_dinamis.pagu');
    }

    public function test_optional_dynamic_attribute_can_be_left_blank(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $this->attachDefinition($layer, 'catatan', ['label' => 'Catatan'], ['is_wajib' => false]);

        $this->actingAs($admin)->post(route('spatial-layers.features.store', $layer), $this->coordinatesPayload([
            'metadata_dinamis' => $this->coreMetadataDinamis(),
        ]))->assertRedirect(route('spatial-layers.show', $layer));

        $this->assertSame(1, $layer->features()->count());
    }

    public function test_metadata_dinamis_stored_separately_from_imported_attributes(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $this->attachDefinition($layer, 'pagu', [], ['is_wajib' => true]);
        $feature = SpatialLayerFeature::create([
            'spatial_layer_id' => $layer->id,
            'geometry' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)'),
            'attributes' => ['KODE_ASLI' => 'ABC123'],
        ]);

        $this->actingAs($admin)->put(route('spatial-layers.features.update', [$layer, $feature]), [
            'geometry_wkt' => 'POINT(127.5 0.8)',
            'metadata_dinamis' => $this->coreMetadataDinamis() + ['pagu' => '5000000'],
        ])->assertRedirect(route('spatial-layers.show', $layer));

        $feature->refresh();
        $this->assertEqualsCanonicalizing($this->coreMetadataDinamis() + ['pagu' => '5000000'], $feature->metadata_dinamis);
        $this->assertSame(['KODE_ASLI' => 'ABC123'], $feature->attributes);
    }

    /**
     * Regresi Opsi B: kalau definisi katalognya `data_type = select` dengan
     * `opsi` terisi, nilai di luar daftar opsi harus ditolak validasi server.
     */
    public function test_select_dynamic_attribute_rejects_value_outside_options(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        // Kode unik (bukan 'status' yang sudah di-seed migration dengan data_type
        // default 'text') supaya firstOrCreate() beneran BUAT definisi baru dengan
        // data_type=select, bukan reuse baris seed yang sudah ada.
        $this->attachDefinition($layer, 'status_progres', [
            'label' => 'Status Progres',
            'data_type' => MetadataDefinition::TYPE_SELECT,
            'opsi' => ['Berjalan', 'Selesai'],
        ]);

        $this->actingAs($admin)->post(route('spatial-layers.features.store', $layer), $this->coordinatesPayload([
            'metadata_dinamis' => ['status_progres' => 'Batal'],
        ]))->assertSessionHasErrors('metadata_dinamis.status_progres');
    }

    /**
     * Regresi: Jenis TANPA atribut tambahan yang dikonfigurasi manual tetap
     * mewajibkan 3 field metadata_dinamis inti (otomatis terpasang ke semua
     * Jenis) — bukan berarti "tidak ada field wajib sama sekali".
     */
    public function test_jenis_without_custom_dynamic_attributes_still_requires_core_fields(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();

        $this->actingAs($admin)->post(route('spatial-layers.features.store', $layer), $this->coordinatesPayload())
            ->assertSessionHasErrors([
                'metadata_dinamis.sumber_data',
                'metadata_dinamis.opd_penanggung_jawab',
                'metadata_dinamis.tanggal_data',
            ]);

        $this->actingAs($admin)->post(route('spatial-layers.features.store', $layer), $this->coordinatesPayload([
            'metadata_dinamis' => $this->coreMetadataDinamis(),
        ]))->assertRedirect(route('spatial-layers.show', $layer));

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
        $this->attachDefinition($layer, 'catatan', ['label' => 'Catatan']);

        $this->actingAs($admin)->get(route('spatial-layers.features.create', $layer))->assertOk();
    }

    public function test_edit_page_renders_with_imported_attributes_and_metadata_dinamis(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $this->attachDefinition($layer, 'pagu');
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
