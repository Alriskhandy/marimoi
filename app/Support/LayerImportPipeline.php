<?php

namespace App\Support;

use App\Models\LayerAttributeMapping;
use App\Models\LayerImport;
use App\Models\MetadataDefinition;
use App\Models\SpatialLayer;
use App\Models\SpatialLayerFeature;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Logika impor file (Shapefile/KMZ/KML) ke `spatial_features`, diekstrak dari
 * LayerImportController (2026-10-06) supaya LayerWizardController bisa
 * memakai jalur yang IDENTIK tanpa duplikasi ~120 baris parsing/validasi.
 * LayerImportController dan LayerWizardController masing-masing hanya
 * memanggil ingest()/process() lalu menentukan redirect-nya sendiri — baca
 * docblock di LayerImportController untuk konteks desain 2 tahap
 * upload→mapping.
 */
class LayerImportPipeline
{
    /**
     * R22/D18 (spec-admin-manajemen-peta.md §5.5 butir 8) — 100 MB per file
     * impor, tanpa chunked upload.
     */
    private const MAX_IMPORT_FILE_BYTES = 100 * 1024 * 1024;

    public function __construct(private SpatialGeometryBatchImporter $importer) {}

    /**
     * Tahap 1: upload file, parsing, deteksi kolom — BELUM menyimpan fitur
     * apa pun. Hasil parsing dicache di `log` sampai pemetaan selesai.
     * Kegagalan parsing TIDAK melempar exception — dikembalikan sebagai
     * baris `LayerImport` berstatus `failed` supaya pemanggil (controller)
     * bisa memutuskan redirect/pesannya sendiri sambil tetap mencatat
     * riwayat percobaan gagal (dibutuhkan wizard untuk ditampilkan ke user
     * saat resume di tahap impor).
     *
     * Kegagalan validasi (jenis input tidak valid, ukuran file melebihi
     * limit) TETAP melempar ValidationException seperti biasa — tidak ada
     * baris yang dicatat untuk kasus ini (konsisten dengan perilaku lama).
     */
    public function ingest(Request $request, SpatialLayer $layer): LayerImport
    {
        $request->validate([
            'input_type' => ['required', Rule::in(['shapefile', 'kmz'])],
            'import_mode' => ['nullable', Rule::in(['replace', 'append'])],
        ]);

        $inputType = $request->input('input_type');
        $importMode = $request->input('import_mode', 'append');

        // Tangkap & simpan salinan file SEBELUM dipanggil ke importer —
        // SpatialGeometryBatchImporter::fromShapefile() memindahkan (move(),
        // bukan copy) file upload sementara ke folder kerja tetapnya, jadi
        // informasi file asli (nama, ukuran, checksum) harus direkam dulu
        // (D.1, R10).
        $fileMeta = $this->captureImportFileMetadata($request, $inputType);

        try {
            $results = $inputType === 'shapefile'
                ? $this->importer->fromShapefile($request->file('shp_file'), $request->file('shx_file'), $request->file('dbf_file'))
                : $this->importer->fromKmz($request->file('kmz_file'));
        } catch (\Exception $e) {
            return $this->recordImport($layer, $fileMeta, $importMode, 'failed', null, 0, $e->getMessage());
        }

        $detectedFields = collect($results)
            ->flatMap(fn (array $result) => array_keys($result['attributes']))
            ->unique()->values()->all();

        return LayerImport::create([
            'layer_id' => $layer->id,
            'original_filename' => $fileMeta['original_filename'],
            'storage_path' => $fileMeta['storage_path'],
            'file_format' => $fileMeta['file_format'],
            'file_size_bytes' => $fileMeta['file_size_bytes'],
            'checksum_sha256' => $fileMeta['checksum_sha256'],
            'import_mode' => $importMode,
            'status' => 'mapping',
            'detected_fields' => $detectedFields,
            'total_features' => count($results),
            'imported_features' => 0,
            'failed_features' => 0,
            'log' => ['features' => $results],
            'started_at' => now(),
            'imported_by' => auth()->id(),
        ]);
    }

    /**
     * Jalur koordinat manual (dipakai wizard Tambah Layer DAN
     * SpatialLayerFeatureController::store() pada Layer yang sudah jadi) —
     * BEDA dari ingest()/process() karena tidak ada kolom yang perlu
     * dipetakan (nama kolom hasil parsing koordinat sudah tetap: NAMA/
     * LATITUDE/LONGITUDE/INPUT_TYPE), jadi fitur langsung tersimpan dalam
     * satu langkah tanpa baris `LayerImport`/tahap mapping terpisah.
     * Mengembalikan jumlah fitur yang disimpan.
     */
    public function ingestCoordinates(Request $request, SpatialLayer $layer): int
    {
        $validated = $request->validate([
            'coordinates' => 'required|array|min:1',
            'coordinates.*.latitude' => 'nullable|numeric|between:-90,90',
            'coordinates.*.longitude' => 'nullable|numeric|between:-180,180',
            'coordinates.*.name' => 'nullable|string|max:255',
            'import_mode' => ['nullable', Rule::in(['replace', 'append'])],
        ]);

        try {
            $results = $this->importer->fromCoordinates($validated['coordinates']);
        } catch (\Exception $e) {
            throw ValidationException::withMessages(['input_type' => $e->getMessage()]);
        }

        $importMode = $validated['import_mode'] ?? 'append';

        DB::transaction(function () use ($results, $layer, $importMode) {
            if ($importMode === 'replace') {
                SpatialLayerFeature::where('layer_id', $layer->id)->delete();
            }

            foreach ($results as $result) {
                $quotedWkt = DB::connection()->getPdo()->quote($result['wkt']);

                SpatialLayerFeature::create([
                    'layer_id' => $layer->id,
                    'geom' => DB::raw("ST_GeomFromText({$quotedWkt}, 4326)"),
                    'properties' => $result['attributes'],
                    'created_by' => auth()->id(),
                ]);
            }
        });

        $layer->refreshFeatureCache();

        return count($results);
    }

    /**
     * Tahap 2: terapkan pemetaan, bangun `properties` final, simpan fitur
     * (mode replace/append, R12), catat `layer_attribute_mappings`, dan
     * selesaikan status impor. Mengembalikan jumlah fitur yang disimpan.
     *
     * Update status import dipindah KE DALAM transaksi saat ekstraksi ini
     * (sebelumnya di luar) — kalau proses mati di antara commit fitur dan
     * update status, insert fitur dan update status sekarang atau-atau,
     * bukan bisa setengah jalan (yang sebelumnya membuka celah proses ulang
     * = fitur dobel kalau user menekan submit lagi).
     */
    public function process(Request $request, SpatialLayer $layer, LayerImport $import): int
    {
        $dynamicAttributes = $this->activeDynamicAttributesFor($layer);
        $definitionsByKode = $dynamicAttributes->pluck('metadataDefinition')->keyBy('kode');

        $validated = $request->validate([
            'mapping' => 'array',
            'mapping.*' => 'nullable|string',
        ] + $this->metadataDinamisRules($layer));

        $metadataDinamis = $validated['metadata_dinamis'] ?? [];
        $mapping = $validated['mapping'] ?? [];
        $gambarPath = $this->storeGambarIfPresent($request);

        $results = $import->log['features'] ?? [];

        DB::transaction(function () use ($results, $mapping, $definitionsByKode, $metadataDinamis, $gambarPath, $layer, $import) {
            if ($import->import_mode === 'replace') {
                SpatialLayerFeature::where('layer_id', $layer->id)->delete();
            }

            foreach ($results as $result) {
                $properties = $this->applyMapping($result['attributes'], $mapping);
                $quotedWkt = DB::connection()->getPdo()->quote($result['wkt']);

                SpatialLayerFeature::create([
                    'layer_id' => $layer->id,
                    'layer_import_id' => $import->id,
                    'geom' => DB::raw("ST_GeomFromText({$quotedWkt}, 4326)"),
                    'properties' => array_merge($properties, $metadataDinamis),
                    'gambar' => $gambarPath,
                    'created_by' => auth()->id(),
                ]);
            }

            // ck_layer_attr_map_target: baris ini HANYA merekam keputusan
            // eksplisit (diabaikan atau dipetakan) — field yang "disimpan apa
            // adanya" (default) bukan keputusan untuk dicatat, sengaja tidak
            // diberi baris di `layer_attribute_mappings`.
            foreach ($import->detected_fields ?? [] as $field) {
                $decision = $mapping[$field] ?? 'keep';

                if ($decision === 'keep') {
                    continue;
                }

                $kode = str_starts_with($decision, 'map:') ? substr($decision, 4) : null;
                $attributeDefinitionId = $kode ? ($definitionsByKode[$kode]->id ?? null) : null;

                // Sejak `layers.map_type_id` dihapus (2026-10-06), Layer tidak
                // lagi bisa punya atribut dinamis aktif — $definitionsByKode
                // selalu kosong, jadi target "map:xxx" tidak akan pernah
                // teresolusi lagi. CHECK constraint ck_layer_attr_map_target
                // mewajibkan baris non-ignored punya attribute_definition_id
                // terisi, jadi baris audit utk target yang tak teresolusi
                // dilewati (field-nya SENDIRI tetap di-rename di properties,
                // lihat applyMapping() — cuma jejak auditnya yang tidak ada).
                if ($decision !== 'ignore' && $attributeDefinitionId === null) {
                    continue;
                }

                LayerAttributeMapping::create([
                    'layer_import_id' => $import->id,
                    'source_field_name' => $field,
                    'attribute_definition_id' => $attributeDefinitionId,
                    'is_ignored' => $decision === 'ignore',
                ]);
            }

            $import->update([
                'status' => 'completed',
                'imported_features' => count($results),
                'failed_features' => 0,
                'log' => [],
                'finished_at' => now(),
            ]);
        });

        $layer->refreshFeatureCache();

        return count($results);
    }

    /**
     * @param  array<string, mixed>  $rawAttributes
     * @param  array<string, string>  $mapping  nama field => 'keep'|'ignore'|'map:{kode}'
     * @return array<string, mixed>
     */
    private function applyMapping(array $rawAttributes, array $mapping): array
    {
        $properties = [];

        foreach ($rawAttributes as $field => $value) {
            $decision = $mapping[$field] ?? 'keep';

            if ($decision === 'ignore') {
                continue;
            }

            if (str_starts_with($decision, 'map:')) {
                $properties[substr($decision, 4)] = $value;
            } else {
                $properties[$field] = $value;
            }
        }

        return $properties;
    }

    /**
     * @return array{original_filename: string, storage_path: string, file_format: string, file_size_bytes: int, checksum_sha256: string}
     */
    private function captureImportFileMetadata(Request $request, string $inputType): array
    {
        if ($inputType === 'shapefile') {
            $request->validate([
                'shp_file' => 'required|file',
                'shx_file' => 'required|file',
                'dbf_file' => 'required|file',
            ]);

            $shp = $request->file('shp_file');
            $shx = $request->file('shx_file');
            $dbf = $request->file('dbf_file');

            $this->assertWithinSizeLimit($shp, 'shp');
            $this->assertWithinSizeLimit($shx, 'shx');
            $this->assertWithinSizeLimit($dbf, 'dbf');

            $dir = 'layer-imports/'.(string) Str::uuid();

            Storage::disk('public')->putFileAs($dir, $shp, 'data.shp');
            Storage::disk('public')->putFileAs($dir, $shx, 'data.shx');
            Storage::disk('public')->putFileAs($dir, $dbf, 'data.dbf');

            return [
                'original_filename' => $shp->getClientOriginalName(),
                'storage_path' => $dir,
                'file_format' => 'shp_zip',
                'file_size_bytes' => $shp->getSize() + $shx->getSize() + $dbf->getSize(),
                'checksum_sha256' => hash_file('sha256', $shp->getRealPath()),
            ];
        }

        $request->validate(['kmz_file' => 'required|file']);

        /** @var UploadedFile $file */
        $file = $request->file('kmz_file');
        $this->assertWithinSizeLimit($file, 'KMZ/KML');
        $extension = strtolower($file->getClientOriginalExtension()) ?: 'kmz';
        $format = $extension === 'kml' ? 'kml' : 'kmz';
        $dir = 'layer-imports/'.(string) Str::uuid();

        Storage::disk('public')->putFileAs($dir, $file, 'data.'.$format);

        return [
            'original_filename' => $file->getClientOriginalName(),
            'storage_path' => $dir,
            'file_format' => $format,
            'file_size_bytes' => $file->getSize(),
            'checksum_sha256' => hash_file('sha256', $file->getRealPath()),
        ];
    }

    private function assertWithinSizeLimit(UploadedFile $file, string $label): void
    {
        if ($file->getSize() <= self::MAX_IMPORT_FILE_BYTES) {
            return;
        }

        $sentMb = round($file->getSize() / 1024 / 1024, 1);
        $limitMb = self::MAX_IMPORT_FILE_BYTES / 1024 / 1024;

        throw ValidationException::withMessages([
            'input_type' => "Ukuran file {$label} ({$sentMb} MB) melebihi batas maksimal {$limitMb} MB per file.",
        ]);
    }

    /**
     * @param  array{original_filename: string, storage_path: string, file_format: string, file_size_bytes: int, checksum_sha256: string}  $fileMeta
     */
    private function recordImport(SpatialLayer $layer, array $fileMeta, string $importMode, string $status, ?int $total, int $imported, ?string $errorMessage): LayerImport
    {
        return LayerImport::create([
            'layer_id' => $layer->id,
            'original_filename' => $fileMeta['original_filename'],
            'storage_path' => $fileMeta['storage_path'],
            'file_format' => $fileMeta['file_format'],
            'file_size_bytes' => $fileMeta['file_size_bytes'],
            'checksum_sha256' => $fileMeta['checksum_sha256'],
            'import_mode' => $importMode,
            'status' => $status,
            'total_features' => $total,
            'imported_features' => $imported,
            'failed_features' => 0,
            'error_message' => $errorMessage,
            'started_at' => now(),
            'finished_at' => now(),
            'imported_by' => auth()->id(),
        ]);
    }

    /**
     * SELALU kosong. Atribut dinamis dulu di-scope per "Jenis Peta" lewat
     * `map_type_dynamic_attributes`, tapi kolom `layers.map_type_id` dilepas
     * 2026-10-06 (migration drop_map_type_id_and_visibility_from_layers_table)
     * sehingga query ini sudah tidak pernah bisa menemukan baris, lalu modul
     * Jenis Peta beserta tabelnya dihapus 2026-10-10 (migration
     * drop_map_types_tables). Method dipertahankan sebagai titik sambung
     * tunggal supaya pemanggilnya (metadataDinamisRules(), $dynamicAttributes
     * di view) tidak perlu diubah bila mekanisme penggantinya nanti ada.
     */
    private function activeDynamicAttributesFor(SpatialLayer $layer): Collection
    {
        return collect();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function metadataDinamisRules(SpatialLayer $layer): array
    {
        $rules = [];

        foreach ($this->activeDynamicAttributesFor($layer) as $attribute) {
            $definition = $attribute->metadataDefinition;
            $fieldRules = [$attribute->is_wajib ? 'required' : 'nullable'];

            if ($definition->data_type === MetadataDefinition::TYPE_SELECT && ! empty($definition->opsi)) {
                $fieldRules[] = Rule::in($definition->opsi);
            } else {
                $fieldRules[] = 'string';
            }

            $rules['metadata_dinamis.'.$definition->kode] = $fieldRules;
        }

        return $rules;
    }

    private function storeGambarIfPresent(Request $request): ?string
    {
        $request->validate(['gambar' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048']);

        if (! $request->hasFile('gambar')) {
            return null;
        }

        $file = $request->file('gambar');
        $fileName = time().'_'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension();

        return $file->storeAs('images/spatial-layer-features', $fileName, 'public');
    }
}
