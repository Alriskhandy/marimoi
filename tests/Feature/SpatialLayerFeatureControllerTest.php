<?php

namespace Tests\Feature;

use App\Models\AdministrativeRegion;
use App\Models\Opd;
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
 * Regresi untuk SpatialLayerFeatureController — skema v3 (plan
 * mellow-weaving-eclipse Fase 3). `attributes` (impor) dan `metadata_dinamis`
 * (terstruktur) dulu 2 kolom jsonb terpisah di v2 — sekarang tergabung jadi
 * satu `properties` (dokumen v3 §5.11 tidak membedakan keduanya). update()
 * MEMERGE metadata_dinamis baru ke atas properties yang sudah ada (bukan
 * overwrite total), jadi atribut impor lama tetap bertahan setelah edit.
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

    private function layer(array $overrides = []): SpatialLayer
    {
        return SpatialLayer::create(array_merge([
            'category_id' => $this->categoryId(),
            'layer_type_id' => 4,
            'code' => 'layer-'.Str::random(8),
            'slug' => 'layer-'.uniqid(),
            'name' => 'Layer Uji',
        ], $overrides));
    }

    /**
     * Sejak `layers.map_type_id` dihapus (2026-10-06, lihat migration
     * drop_map_type_id_and_visibility_from_layers_table), Layer TIDAK LAGI
     * bisa dihubungkan ke Jenis Peta — `activeDynamicAttributesFor()` selalu
     * mengembalikan collection kosong untuk SEMUA Layer (lihat
     * SpatialLayerFeatureController), jadi field metadata_dinamis ini (dan
     * aturan wajibnya) tidak lagi tervalidasi/tersimpan sama sekali. Helper
     * ini dipertahankan hanya supaya test lama yang mengirim payload ini
     * tetap terbaca jelas maksudnya — nilainya kini murni diabaikan backend.
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
    }

    /**
     * Regresi: input_type SELAIN 'coordinates' (shapefile/kmz) ditolak di sini
     * sejak Fase D.2 — impor file sudah pindah ke wizard 2 tahap
     * (LayerImportController::upload()/editMapping()/processMapping(), lihat
     * tests/Feature/LayerImportControllerTest.php), bukan lagi lewat store()
     * satu langkah ini.
     */
    public function test_store_rejects_file_based_input_types(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();

        $this->actingAs($admin)->post(route('spatial-layers.features.store', $layer), [
            'input_type' => 'kmz',
            'metadata_dinamis' => $this->coreMetadataDinamis(),
        ])->assertSessionHasErrors('input_type');
    }

    public function test_store_requires_input_type(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();

        $this->actingAs($admin)->post(route('spatial-layers.features.store', $layer), [
            'metadata_dinamis' => $this->coreMetadataDinamis(),
        ])->assertSessionHasErrors('input_type');
    }

    /**
     * Regresi: sejak `layers.map_type_id` dihapus (2026-10-06), setiap Layer
     * tidak lagi bisa punya atribut dinamis aktif (`activeDynamicAttributesFor()`
     * selalu kosong) — field `metadata_dinamis` yang dikirim tidak lagi
     * divalidasi/disimpan sama sekali, terlepas isinya apa. Menggantikan
     * test_required_dynamic_attribute_is_enforced/test_jenis_without_custom_dynamic_attributes_still_requires_core_fields
     * yang menguji perilaku lama (field itu wajib) — perilaku itu sengaja
     * sudah tidak ada lagi, bukan regresi.
     */
    public function test_store_succeeds_without_metadata_dinamis_since_dynamic_attributes_no_longer_apply(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();

        $this->actingAs($admin)->post(route('spatial-layers.features.store', $layer), $this->coordinatesPayload())
            ->assertRedirect(route('spatial-layers.show', $layer));

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

        $this->actingAs($admin)->get(route('spatial-layers.features.create', $layer))->assertOk();
    }

    public function test_edit_page_renders_with_imported_attributes(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $feature = SpatialLayerFeature::create([
            'layer_id' => $layer->id,
            'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)'),
            'properties' => ['KODE_ASLI' => 'ABC123', 'pagu' => '5000000'],
        ]);

        $this->actingAs($admin)->get(route('spatial-layers.features.edit', [$layer, $feature]))
            ->assertOk()
            ->assertSee('KODE_ASLI')
            ->assertSee('POINT');
    }

    /**
     * Fase D.1 (spec-admin-manajemen-peta.md §5.5 butir 1): setiap impor file
     * (bukan input koordinat manual) wajib menghasilkan satu baris riwayat
     * `layer_imports` — sebelumnya impor sama sekali tidak tercatat.
     */
    public function test_coordinates_input_does_not_create_layer_import_row(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();

        $this->actingAs($admin)->post(route('spatial-layers.features.store', $layer), $this->coordinatesPayload([
            'metadata_dinamis' => $this->coreMetadataDinamis(),
        ]))->assertRedirect(route('spatial-layers.show', $layer));

        $this->assertSame(0, DB::table('layer_imports')->where('layer_id', $layer->id)->count());
    }

    /**
     * R18: feature_count/bbox (kolom cache di `layers`) harus ikut diperbarui
     * setiap kali fitur berubah lewat modul admin baru — sebelumnya hanya
     * App\Support\SpatialFeaturesV3Sync (jembatan modul lama) yang melakukan ini.
     */
    public function test_feature_count_cache_is_refreshed_after_store_and_destroy(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();

        $this->actingAs($admin)->post(route('spatial-layers.features.store', $layer), $this->coordinatesPayload([
            'metadata_dinamis' => $this->coreMetadataDinamis(),
        ]));

        $this->assertSame(1, $layer->fresh()->feature_count);

        $feature = $layer->features()->first();
        $this->actingAs($admin)->delete(route('spatial-layers.features.destroy', [$layer, $feature]));

        $this->assertSame(0, $layer->fresh()->feature_count);
    }

    /**
     * §5.7 butir 3 — bulk edit atribut: set satu atribut untuk beberapa Data
     * Spasial sekaligus, porting dari DataSpatialController::bulkUpdateAttribute()
     * (modul lama). Bentuknya satu kunci + satu aksi (set/remove), bukan merge
     * properties penuh.
     */
    public function test_bulk_update_attribute_sets_value_for_selected_features(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $featureA = SpatialLayerFeature::create(['layer_id' => $layer->id, 'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)'), 'properties' => ['kondisi' => 'Buruk']]);
        $featureB = SpatialLayerFeature::create(['layer_id' => $layer->id, 'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.6, 0.9), 4326)'), 'properties' => []]);
        $untouched = SpatialLayerFeature::create(['layer_id' => $layer->id, 'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.7, 1.0), 4326)'), 'properties' => ['kondisi' => 'Awal']]);

        $this->actingAs($admin)->post(route('spatial-layers.features.bulk-update-attribute', $layer), [
            'ids' => [$featureA->id, $featureB->id],
            'action' => 'set',
            'key' => 'kondisi',
            'value' => 'Baik',
        ])->assertRedirect(route('spatial-layers.show', $layer));

        $this->assertSame('Baik', $featureA->fresh()->properties['kondisi']);
        $this->assertSame('Baik', $featureB->fresh()->properties['kondisi']);
        $this->assertSame('Awal', $untouched->fresh()->properties['kondisi']);
    }

    public function test_bulk_update_attribute_can_remove_key(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $feature = SpatialLayerFeature::create(['layer_id' => $layer->id, 'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)'), 'properties' => ['kondisi' => 'Buruk', 'lain' => 'x']]);

        $this->actingAs($admin)->post(route('spatial-layers.features.bulk-update-attribute', $layer), [
            'ids' => [$feature->id],
            'action' => 'remove',
            'key' => 'kondisi',
        ])->assertRedirect(route('spatial-layers.show', $layer));

        $feature->refresh();
        $this->assertArrayNotHasKey('kondisi', $feature->properties);
        $this->assertSame('x', $feature->properties['lain']);
    }

    public function test_bulk_update_attribute_rejects_invalid_key_name(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $feature = SpatialLayerFeature::create(['layer_id' => $layer->id, 'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)')]);

        $this->actingAs($admin)->post(route('spatial-layers.features.bulk-update-attribute', $layer), [
            'ids' => [$feature->id],
            'action' => 'set',
            'key' => '123 invalid',
            'value' => 'x',
        ])->assertSessionHasErrors('key');
    }

    public function test_bulk_update_attribute_only_affects_features_of_this_layer(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $otherLayer = $this->layer();
        $foreignFeature = SpatialLayerFeature::create(['layer_id' => $otherLayer->id, 'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)'), 'properties' => ['kondisi' => 'Awal']]);

        $this->actingAs($admin)->post(route('spatial-layers.features.bulk-update-attribute', $layer), [
            'ids' => [$foreignFeature->id],
            'action' => 'set',
            'key' => 'kondisi',
            'value' => 'Diubah',
        ]);

        $this->assertSame('Awal', $foreignFeature->fresh()->properties['kondisi']);
    }

    public function test_bulk_destroy_deletes_selected_features_and_refreshes_cache(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $featureA = SpatialLayerFeature::create(['layer_id' => $layer->id, 'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)')]);
        $featureB = SpatialLayerFeature::create(['layer_id' => $layer->id, 'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.6, 0.9), 4326)')]);
        $kept = SpatialLayerFeature::create(['layer_id' => $layer->id, 'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.7, 1.0), 4326)')]);
        $layer->refreshFeatureCache();

        $this->actingAs($admin)->post(route('spatial-layers.features.bulk-destroy', $layer), [
            'ids' => [$featureA->id, $featureB->id],
        ])->assertRedirect(route('spatial-layers.show', $layer));

        $this->assertSame(1, $layer->features()->count());
        $this->assertTrue($layer->features()->where('id', $kept->id)->exists());
        $this->assertSame(1, $layer->fresh()->feature_count);
    }

    public function test_bulk_destroy_requires_at_least_one_id(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();

        $this->actingAs($admin)->post(route('spatial-layers.features.bulk-destroy', $layer), [
            'ids' => [],
        ])->assertSessionHasErrors('ids');
    }

    public function test_show_page_renders_bulk_actions_bar_and_row_checkboxes(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $feature = SpatialLayerFeature::create(['layer_id' => $layer->id, 'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)')]);

        $response = $this->actingAs($admin)->get(route('spatial-layers.show', $layer));

        $response->assertOk()
            ->assertSee('id="featureBulkActionsBar"', false)
            ->assertSee('id="featureSelectAll"', false)
            ->assertSee('id="check-feature-'.$feature->id.'"', false)
            ->assertSee(route('spatial-layers.features.bulk-update-attribute', $layer), false)
            ->assertSee(route('spatial-layers.features.bulk-destroy', $layer), false);
    }

    /**
     * Tombol "Pindah ke Layer Lain" (bulk) hanya masuk akal kalau ADA Layer
     * lain untuk dijadikan tujuan — kalau Layer ini satu-satunya, tombolnya
     * disembunyikan (SpatialLayerController::show()'s $moveTargetLayers).
     */
    public function test_show_page_renders_bulk_move_button_only_when_other_layers_exist(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $other = $this->layer(['name' => 'Layer Lain']);

        $this->actingAs($admin)->get(route('spatial-layers.show', $layer))
            ->assertOk()
            ->assertSee('id="bulkMoveFeatureModal"', false)
            ->assertSee($other->name)
            ->assertSee(route('spatial-layers.features.bulk-move', $layer), false);
    }

    public function test_show_page_hides_bulk_move_button_when_no_other_layers_exist(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();

        $this->actingAs($admin)->get(route('spatial-layers.show', $layer))
            ->assertOk()
            ->assertDontSee('id="bulkMoveFeatureModal"', false);
    }

    /**
     * Edit style langsung dari popup di peta (bukan cuma lewat halaman Style
     * Layer terpisah) — halaman ini butuh meta csrf-token untuk fetch() PUT
     * AJAX ke SpatialLayerFeatureController::updateStyle(), dan URL template
     * endpoint-nya harus ter-render di script.
     */
    public function test_show_page_wires_inline_map_style_editing(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();

        // @json() di Blade meng-escape slash ("/" -> "\/") — bandingkan
        // dengan bentuk json_encode yang sama, bukan string route() mentah.
        $expectedUrlJson = trim(json_encode(route('spatial-layers.features.update-style', [$layer, '__FEATURE_ID__'])), '"');

        $this->actingAs($admin)->get(route('spatial-layers.show', $layer))
            ->assertOk()
            ->assertSee('name="csrf-token"', false)
            ->assertSee('data-map-style-save', false)
            ->assertSee($expectedUrlJson, false);
    }

    /**
     * Regresi: komentar HTML biasa (`<!-- ... -->`) yang isinya mengandung
     * literal `@push(...)` membuat Blade mengenali `@push` di DALAM
     * komentar sebagai directive sungguhan — komentarnya jadi tidak pernah
     * ditutup di output HTML (teks `-->` ikut "termakan" oleh compiler),
     * sehingga browser menganggap SISA SELURUH HALAMAN (termasuk semua
     * `<link rel="stylesheet">` di `<head>`) sebagai komentar yang belum
     * selesai — akibatnya halaman tampil TANPA style sama sekali walau
     * kontennya tetap ada. Baris dengan "@" harus pakai komentar Blade
     * ({{-- --}}, yang di-strip saat kompilasi) bukan komentar HTML biasa.
     */
    public function test_show_page_head_closes_properly_and_is_not_swallowed_by_a_broken_html_comment(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();

        $html = $this->actingAs($admin)->get(route('spatial-layers.show', $layer))->assertOk()->getContent();

        $this->assertStringContainsString('</head>', $html);
        $this->assertStringContainsString('backend_baru/assets/css/bootstrap.min.css', $html);
        $this->assertLessThan(
            strpos($html, '</head>'),
            strpos($html, 'backend_baru/assets/css/bootstrap.min.css'),
            'Link stylesheet Bootstrap harus muncul SEBELUM </head> ditutup — kalau tidak, berarti ada komentar yang belum ditutup menelan seluruh <head>.'
        );
    }

    /**
     * §5.7 butir 4 — `region_id` sudah dipakai peta publik (peta-v2) tapi
     * sebelum ini belum bisa diisi dari UI admin sama sekali.
     */
    public function test_coordinates_input_can_set_region_id(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $region = AdministrativeRegion::create(['code_kemendagri' => 'RG-1', 'name' => 'Kota Ternate', 'level' => 'kabupaten_kota', 'is_active' => true]);

        $this->actingAs($admin)->post(route('spatial-layers.features.store', $layer), $this->coordinatesPayload([
            'region_id' => $region->id,
            'metadata_dinamis' => $this->coreMetadataDinamis(),
        ]))->assertRedirect(route('spatial-layers.show', $layer));

        $this->assertSame($region->id, $layer->features()->first()->region_id);
    }

    public function test_update_can_set_region_id(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $region = AdministrativeRegion::create(['code_kemendagri' => 'RG-1', 'name' => 'Kota Ternate', 'level' => 'kabupaten_kota', 'is_active' => true]);
        $feature = SpatialLayerFeature::create(['layer_id' => $layer->id, 'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)')]);

        $this->actingAs($admin)->put(route('spatial-layers.features.update', [$layer, $feature]), [
            'geometry_wkt' => 'POINT(127.5 0.8)',
            'region_id' => $region->id,
            'metadata_dinamis' => $this->coreMetadataDinamis(),
        ])->assertRedirect(route('spatial-layers.show', $layer));

        $this->assertSame($region->id, $feature->fresh()->region_id);
    }

    public function test_invalid_region_id_is_rejected(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();

        $this->actingAs($admin)->post(route('spatial-layers.features.store', $layer), $this->coordinatesPayload([
            'region_id' => 999999,
            'metadata_dinamis' => $this->coreMetadataDinamis(),
        ]))->assertSessionHasErrors('region_id');
    }

    public function test_create_and_edit_pages_render_region_select(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        AdministrativeRegion::create(['code_kemendagri' => 'RG-1', 'name' => 'Kota Ternate', 'level' => 'kabupaten_kota', 'is_active' => true]);
        $feature = SpatialLayerFeature::create(['layer_id' => $layer->id, 'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)')]);

        $this->actingAs($admin)->get(route('spatial-layers.features.create', $layer))
            ->assertOk()->assertSee('Kota Ternate');

        $this->actingAs($admin)->get(route('spatial-layers.features.edit', [$layer, $feature]))
            ->assertOk()->assertSee('Kota Ternate');
    }

    /**
     * Style satu Layer (layer_styles.definition) berlaku untuk SEMUA Data
     * Spasial miliknya secara default — `style_override` memungkinkan SATU
     * Data Spasial dikustom sendiri (mis. tiap polygon Kab/Kota diberi warna
     * berbeda di Layer "Peta Administrasi Kab/Kota" yang sama). Diatur di
     * halaman "Style Layer" (LayerStyleController::index(), lewat
     * updateStyle() di bawah) bersama style default, BUKAN di halaman
     * "Kelola Data Spasial" ini — supaya tidak ada dua tempat berbeda untuk
     * satu urusan (style). Halaman ini hanya menautkan ke sana.
     */
    public function test_edit_page_links_to_style_management_instead_of_having_its_own_style_fields(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $feature = SpatialLayerFeature::create(['layer_id' => $layer->id, 'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)')]);

        $this->actingAs($admin)->get(route('spatial-layers.features.edit', [$layer, $feature]))
            ->assertOk()
            ->assertSee(route('spatial-layers.styles.index', $layer), false)
            ->assertDontSee('name="style_color"', false);
    }

    public function test_geojson_endpoint_includes_feature_style_override(): void
    {
        $layer = $this->layer();
        SpatialLayerFeature::create([
            'layer_id' => $layer->id,
            'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)'),
            'style_override' => ['color' => '#ff0000', 'size' => 10, 'opacity' => 0.5, 'is_marker' => false, 'icon' => null],
        ]);

        $response = $this->get(route('peta-v2.geojson', $layer));

        $response->assertOk();
        $this->assertSame('#ff0000', $response->json('features.0.properties.style_override.color'));
    }

    /**
     * Pindahkan Data Spasial dari satu Layer ke Layer lain — dua bentuk:
     * baris terpilih saja (bulkMoveToLayer, tombol bulk di show.blade.php)
     * atau SEMUA Data Spasial satu Layer sekaligus (moveAllFeatures, tombol
     * per baris di index.blade.php, untuk menggabungkan Layer duplikat).
     */
    public function test_bulk_move_to_layer_moves_only_selected_features(): void
    {
        $admin = $this->admin();
        $source = $this->layer(['name' => 'Layer Asal']);
        $target = $this->layer(['name' => 'Layer Tujuan']);
        $moved = SpatialLayerFeature::create(['layer_id' => $source->id, 'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)')]);
        $stays = SpatialLayerFeature::create(['layer_id' => $source->id, 'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.6, 0.9), 4326)')]);

        $this->actingAs($admin)->post(route('spatial-layers.features.bulk-move', $source), [
            'ids' => [$moved->id],
            'target_layer_id' => $target->id,
        ])->assertRedirect(route('spatial-layers.show', $source));

        $this->assertSame($target->id, $moved->refresh()->layer_id);
        $this->assertSame($source->id, $stays->refresh()->layer_id);
    }

    public function test_bulk_move_to_layer_refreshes_feature_count_cache_on_both_layers(): void
    {
        $admin = $this->admin();
        $source = $this->layer();
        $target = $this->layer();
        $feature = SpatialLayerFeature::create(['layer_id' => $source->id, 'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)')]);

        $this->actingAs($admin)->post(route('spatial-layers.features.bulk-move', $source), [
            'ids' => [$feature->id],
            'target_layer_id' => $target->id,
        ]);

        $this->assertSame(0, $source->refresh()->feature_count);
        $this->assertSame(1, $target->refresh()->feature_count);
    }

    public function test_bulk_move_to_layer_rejects_same_layer_as_target(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();
        $feature = SpatialLayerFeature::create(['layer_id' => $layer->id, 'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)')]);

        $this->actingAs($admin)->post(route('spatial-layers.features.bulk-move', $layer), [
            'ids' => [$feature->id],
            'target_layer_id' => $layer->id,
        ])->assertSessionHasErrors('target_layer_id');
    }

    public function test_admin_opd_cannot_bulk_move_features_into_another_opds_layer(): void
    {
        $opd = Opd::create(['name' => 'Dinas Uji', 'singkatan' => Str::upper(Str::random(5))]);
        $otherOpd = Opd::create(['name' => 'Dinas Lain', 'singkatan' => Str::upper(Str::random(5))]);
        $role = Role::create(['name' => 'Admin OPD', 'slug' => 'admin-opd', 'description' => null]);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'spatial-layers.edit', 'guard_name' => 'web']));
        $user = User::factory()->create(['role_id' => $role->id, 'opd_id' => $opd->id]);
        $source = $this->layer(['opd_id' => $opd->id]);
        $target = $this->layer(['opd_id' => $otherOpd->id]);
        $feature = SpatialLayerFeature::create(['layer_id' => $source->id, 'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)')]);

        $this->actingAs($user)->post(route('spatial-layers.features.bulk-move', $source), [
            'ids' => [$feature->id],
            'target_layer_id' => $target->id,
        ])->assertForbidden();
    }

    public function test_move_all_features_moves_every_feature_of_the_layer(): void
    {
        $admin = $this->admin();
        $source = $this->layer();
        $target = $this->layer();
        SpatialLayerFeature::create(['layer_id' => $source->id, 'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)')]);
        SpatialLayerFeature::create(['layer_id' => $source->id, 'geom' => DB::raw('ST_SetSRID(ST_MakePoint(127.6, 0.9), 4326)')]);

        $this->actingAs($admin)->post(route('spatial-layers.features.move-all', $source), [
            'target_layer_id' => $target->id,
        ])->assertRedirect(route('spatial-layers.index'));

        $this->assertSame(0, SpatialLayerFeature::where('layer_id', $source->id)->count());
        $this->assertSame(2, SpatialLayerFeature::where('layer_id', $target->id)->count());
    }

    public function test_move_all_features_rejects_same_layer_as_target(): void
    {
        $admin = $this->admin();
        $layer = $this->layer();

        $this->actingAs($admin)->post(route('spatial-layers.features.move-all', $layer), [
            'target_layer_id' => $layer->id,
        ])->assertSessionHasErrors('target_layer_id');
    }
}
