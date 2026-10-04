<?php

namespace Tests\Feature;

use App\Models\LayerImport;
use App\Models\MapType;
use App\Models\MetadataDefinition;
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
 * Fase D.2/D.4 (spec-admin-manajemen-peta.md §5.5 butir 3 & 7) — impor file
 * (Shapefile/KMZ/KML) lewat wizard 2 tahap: upload() mem-parsing & mendeteksi
 * kolom, editMapping()/processMapping() membiarkan admin memetakan kolom ke
 * atribut standar sebelum data benar-benar disimpan. Plus halaman riwayat
 * impor per Layer (read-only, satu-satunya jejak perubahan data layer).
 */
class LayerImportControllerTest extends TestCase
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
        $jenis = MapType::where('slug', 'tematik')->firstOrFail();

        return SpatialLayer::create(array_merge([
            'category_id' => $this->categoryId(),
            'layer_type_id' => 4,
            'code' => 'layer-'.Str::random(8),
            'slug' => 'layer-'.Str::random(8),
            'name' => 'Layer Uji',
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

    private function kmlUploadedFile(string $placemarkName = 'Titik KML Uji', array $extraData = []): UploadedFile
    {
        $extra = '';
        foreach ($extraData as $key => $value) {
            $extra .= "<{$key}>{$value}</{$key}>";
        }

        $kml = <<<KML
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Document>
    <Placemark>
      <name>{$placemarkName}</name>
      <description>{$extra}</description>
      <Point><coordinates>127.5,0.8,0</coordinates></Point>
    </Placemark>
  </Document>
</kml>
KML;

        $path = tempnam(sys_get_temp_dir(), 'kml').'.kml';
        file_put_contents($path, $kml);

        return new UploadedFile($path, 'lokasi.kml', 'application/vnd.google-earth.kml+xml', null, true);
    }

    private function import(SpatialLayer $layer, array $overrides = []): LayerImport
    {
        return LayerImport::create(array_merge([
            'layer_id' => $layer->id,
            'original_filename' => 'data.kml',
            'storage_path' => 'layer-imports/'.Str::uuid(),
            'file_format' => 'kml',
            'file_size_bytes' => 1024,
            'checksum_sha256' => str_repeat('a', 64),
            'import_mode' => 'append',
            'status' => 'completed',
            'total_features' => 3,
            'imported_features' => 3,
            'failed_features' => 0,
        ], $overrides));
    }

    public function test_index_page_lists_import_history(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $this->import($layer, ['original_filename' => 'jalan-provinsi.kml']);

        $this->actingAs($admin)->get(route('spatial-layers.imports.index', $layer))
            ->assertOk()
            ->assertSee('jalan-provinsi.kml');
    }

    public function test_index_page_shows_empty_state_without_imports(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();

        $this->actingAs($admin)->get(route('spatial-layers.imports.index', $layer))
            ->assertOk()
            ->assertSee('Belum ada riwayat impor');
    }

    public function test_download_log_returns_json_with_error_message(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $import = $this->import($layer, ['status' => 'failed', 'error_message' => 'File KMZ/KML tidak berisi data geometrik yang valid.', 'imported_features' => 0]);

        $response = $this->actingAs($admin)->get(route('spatial-layers.imports.log', [$layer, $import]));

        $response->assertOk()->assertJson([
            'status' => 'failed',
            'error_message' => 'File KMZ/KML tidak berisi data geometrik yang valid.',
        ]);
    }

    public function test_admin_opd_cannot_view_import_history_of_another_opd_layer(): void
    {
        $opd = Opd::create(['name' => 'Dinas Uji', 'singkatan' => Str::upper(Str::random(5))]);
        $otherOpd = Opd::create(['name' => 'Dinas Lain', 'singkatan' => Str::upper(Str::random(5))]);
        $role = Role::create(['name' => 'Admin OPD', 'slug' => 'admin-opd', 'description' => null]);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'spatial-layers.view', 'guard_name' => 'web']));
        $user = User::factory()->create(['role_id' => $role->id, 'opd_id' => $opd->id]);
        $layer = $this->layer(['opd_id' => $otherOpd->id]);

        $this->actingAs($user)->get(route('spatial-layers.imports.index', $layer))->assertForbidden();
    }

    /**
     * D.2 — alur penuh: upload mendeteksi kolom & pindah ke status 'mapping'
     * (BELUM menyimpan fitur apa pun), lalu processMapping() dengan pilihan
     * default "simpan apa adanya" benar-benar menyimpan fitur & melengkapi
     * riwayat impor.
     */
    public function test_upload_detects_fields_without_saving_features_yet(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $layer = $this->layer();

        $response = $this->actingAs($admin)->post(route('spatial-layers.imports.upload', $layer), [
            'input_type' => 'kmz',
            'kmz_file' => $this->kmlUploadedFile(),
        ]);

        $import = LayerImport::where('layer_id', $layer->id)->firstOrFail();
        $response->assertRedirect(route('spatial-layers.imports.mapping.edit', [$layer, $import]));

        $this->assertSame('mapping', $import->status);
        $this->assertSame(0, $layer->features()->count());
        $this->assertContains('NAMA', $import->detected_fields);
        $this->assertTrue(Storage::disk('public')->exists($import->storage_path.'/data.kml'));
    }

    public function test_mapping_page_shows_detected_fields(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $layer = $this->layer();
        $this->actingAs($admin)->post(route('spatial-layers.imports.upload', $layer), [
            'input_type' => 'kmz',
            'kmz_file' => $this->kmlUploadedFile(),
        ]);
        $import = LayerImport::where('layer_id', $layer->id)->firstOrFail();

        $this->actingAs($admin)->get(route('spatial-layers.imports.mapping.edit', [$layer, $import]))
            ->assertOk()
            ->assertSee('NAMA')
            ->assertSee('Simpan apa adanya');
    }

    public function test_processing_mapping_with_keep_default_saves_features(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $layer = $this->layer();
        $this->actingAs($admin)->post(route('spatial-layers.imports.upload', $layer), [
            'input_type' => 'kmz',
            'kmz_file' => $this->kmlUploadedFile('Titik Uji'),
        ]);
        $import = LayerImport::where('layer_id', $layer->id)->firstOrFail();

        $this->actingAs($admin)->post(route('spatial-layers.imports.mapping.process', [$layer, $import]), [
            'metadata_dinamis' => $this->coreMetadataDinamis(),
        ])->assertRedirect(route('spatial-layers.show', $layer));

        $this->assertSame(1, $layer->features()->count());
        $feature = $layer->features()->first();
        $this->assertSame('Titik Uji', $feature->properties['NAMA']);
        $this->assertSame($import->id, $feature->layer_import_id);

        $import->refresh();
        $this->assertSame('completed', $import->status);
        $this->assertSame(1, $import->imported_features);
    }

    /**
     * Kolom yang dipetakan admin ke atribut standar harus muncul di
     * `properties` dengan kunci standar itu (bukan nama kolom mentah), dan
     * tercatat sebagai `layer_attribute_mappings`.
     */
    public function test_field_mapped_to_standard_attribute_is_renamed_in_properties(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $layer = $this->layer();
        $this->attachDefinition($layer, 'catatan', ['label' => 'Catatan']);
        $this->actingAs($admin)->post(route('spatial-layers.imports.upload', $layer), [
            'input_type' => 'kmz',
            'kmz_file' => $this->kmlUploadedFile(),
        ]);
        $import = LayerImport::where('layer_id', $layer->id)->firstOrFail();

        $this->actingAs($admin)->post(route('spatial-layers.imports.mapping.process', [$layer, $import]), [
            'mapping' => ['DESCRIPTION' => 'map:catatan'],
            'metadata_dinamis' => $this->coreMetadataDinamis(),
        ])->assertRedirect(route('spatial-layers.show', $layer));

        $feature = $layer->features()->first();
        $this->assertArrayNotHasKey('DESCRIPTION', $feature->properties);

        $this->assertDatabaseHas('layer_attribute_mappings', [
            'layer_import_id' => $import->id,
            'source_field_name' => 'DESCRIPTION',
            'is_ignored' => false,
        ]);
    }

    public function test_field_marked_ignored_is_dropped_from_properties(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $layer = $this->layer();
        $this->actingAs($admin)->post(route('spatial-layers.imports.upload', $layer), [
            'input_type' => 'kmz',
            'kmz_file' => $this->kmlUploadedFile(),
        ]);
        $import = LayerImport::where('layer_id', $layer->id)->firstOrFail();

        $this->actingAs($admin)->post(route('spatial-layers.imports.mapping.process', [$layer, $import]), [
            'mapping' => ['INPUT_TYPE' => 'ignore'],
            'metadata_dinamis' => $this->coreMetadataDinamis(),
        ])->assertRedirect(route('spatial-layers.show', $layer));

        $feature = $layer->features()->first();
        $this->assertArrayNotHasKey('INPUT_TYPE', $feature->properties);

        $this->assertDatabaseHas('layer_attribute_mappings', [
            'layer_import_id' => $import->id,
            'source_field_name' => 'INPUT_TYPE',
            'is_ignored' => true,
        ]);
    }

    public function test_upload_failure_is_recorded_and_redirects_to_create_page(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $layer = $this->layer();

        $path = tempnam(sys_get_temp_dir(), 'kml').'.kml';
        file_put_contents($path, "<?xml version=\"1.0\"?>\n<kml xmlns=\"http://www.opengis.net/kml/2.2\"><Document></Document></kml>");
        $file = new UploadedFile($path, 'kosong.kml', 'application/vnd.google-earth.kml+xml', null, true);

        $this->actingAs($admin)->post(route('spatial-layers.imports.upload', $layer), [
            'input_type' => 'kmz',
            'kmz_file' => $file,
        ])->assertRedirect(route('spatial-layers.features.create', $layer))
            ->assertSessionHasErrors('input_type');

        $this->assertDatabaseHas('layer_imports', [
            'layer_id' => $layer->id,
            'status' => 'failed',
        ]);
        $this->assertSame(0, $layer->features()->count());

        unlink($path);
    }

    public function test_replace_mode_deletes_existing_features_before_inserting_new_ones(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $layer = $this->layer();
        SpatialLayerFeature::create([
            'layer_id' => $layer->id,
            'geom' => DB::raw('ST_SetSRID(ST_MakePoint(100.0, 0.0), 4326)'),
            'properties' => ['NAMA' => 'Fitur Lama'],
        ]);

        $this->actingAs($admin)->post(route('spatial-layers.imports.upload', $layer), [
            'input_type' => 'kmz',
            'kmz_file' => $this->kmlUploadedFile('Fitur Baru'),
            'import_mode' => 'replace',
        ]);
        $import = LayerImport::where('layer_id', $layer->id)->firstOrFail();
        $this->assertSame('replace', $import->import_mode);

        $this->actingAs($admin)->post(route('spatial-layers.imports.mapping.process', [$layer, $import]), [
            'metadata_dinamis' => $this->coreMetadataDinamis(),
        ])->assertRedirect(route('spatial-layers.show', $layer));

        $this->assertSame(1, $layer->features()->count());
        $this->assertSame('Fitur Baru', $layer->features()->first()->properties['NAMA']);
    }

    /**
     * D.6/R22 (spec-admin-manajemen-peta.md §5.5 butir 8): file impor >100 MB
     * ditolak dengan pesan yang menyebut ukuran file & batasnya.
     */
    public function test_oversized_kmz_upload_is_rejected(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $oversized = UploadedFile::fake()->create('terlalu-besar.kmz', 102401);

        $this->actingAs($admin)->post(route('spatial-layers.imports.upload', $layer), [
            'input_type' => 'kmz',
            'kmz_file' => $oversized,
        ])->assertSessionHasErrors('input_type');

        $this->assertStringContainsString('100 MB', session('errors')->first('input_type'));
        $this->assertSame(0, $layer->features()->count());
        $this->assertSame(0, DB::table('layer_imports')->where('layer_id', $layer->id)->count());
    }

    public function test_mapping_page_404_when_import_already_completed(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $import = $this->import($layer, ['status' => 'completed']);

        $this->actingAs($admin)->get(route('spatial-layers.imports.mapping.edit', [$layer, $import]))
            ->assertNotFound();
    }
}
