<?php

namespace App\Http\Controllers;

use App\Models\LayerAttributeMapping;
use App\Models\LayerImport;
use App\Models\MapTypeDynamicAttribute;
use App\Models\MetadataDefinition;
use App\Models\SpatialLayer;
use App\Models\SpatialLayerFeature;
use App\Support\SpatialGeometryBatchImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Impor file (Shapefile/KMZ/KML) ke `spatial_features`, lewat wizard 2 tahap
 * (D.2, spec-admin-manajemen-peta.md §5.5 butir 3): upload() mem-parsing file
 * & mendeteksi kolom, lalu editMapping()/processMapping() membiarkan admin
 * memetakan tiap kolom ke atribut standar (`metadata_definitions`) atau
 * menandainya "simpan apa adanya"/"abaikan", baru setelah itu fitur benar-benar
 * disimpan. Input koordinat manual TIDAK lewat sini — lihat
 * SpatialLayerFeatureController (kolomnya tetap/tidak perlu dipetakan).
 *
 * Hasil parsing mentah (wkt + attributes per baris) disimpan SEMENTARA di
 * `layer_imports.log` (jsonb, kolom serba-guna karena tidak ada tempat lain
 * untuk menyimpan "pekerjaan dalam proses" lintas request) selama status
 * `mapping` — dibersihkan begitu processMapping() selesai, supaya tidak jadi
 * beban permanen.
 */
class LayerImportController extends Controller
{
    /**
     * R22/D18 (spec-admin-manajemen-peta.md §5.5 butir 8) — 100 MB per file
     * impor, tanpa chunked upload. Prasyarat server (`upload_max_filesize`/
     * `post_max_size` PHP, `client_max_body_size` Nginx) harus ≥ ini juga,
     * atau request akan ditolak web server sebelum sampai ke validasi ini.
     */
    private const MAX_IMPORT_FILE_BYTES = 100 * 1024 * 1024;

    public function index(SpatialLayer $spatialLayer)
    {
        $this->authorizeOpdAccess($spatialLayer);

        $imports = $spatialLayer->imports()->with('importedBy')->latest('created_at')->get();

        return view('backend.pages.spatial-layers.imports.index', [
            'layer' => $spatialLayer,
            'imports' => $imports,
        ]);
    }

    public function downloadLog(SpatialLayer $spatialLayer, LayerImport $import): JsonResponse
    {
        $this->authorizeOpdAccess($spatialLayer);
        abort_unless($import->layer_id === $spatialLayer->id, 404);

        $payload = [
            'original_filename' => $import->original_filename,
            'status' => $import->status,
            'error_message' => $import->error_message,
            'log' => $import->log,
        ];

        return response()->json($payload, 200, [
            'Content-Disposition' => 'attachment; filename="import-'.$import->id.'-log.json"',
        ]);
    }

    /**
     * Tahap 1: upload file, parsing, deteksi kolom — BELUM menyimpan fitur
     * apa pun. Hasil parsing dicache di `log` sampai pemetaan selesai.
     */
    public function upload(Request $request, SpatialLayer $spatialLayer, SpatialGeometryBatchImporter $importer)
    {
        $this->authorizeOpdAccess($spatialLayer);

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
                ? $this->importFromShapefile($request, $importer)
                : $this->importFromKmz($request, $importer);
        } catch (\Exception $e) {
            $this->recordImport($spatialLayer, $fileMeta, $importMode, 'failed', null, 0, $e->getMessage());

            return redirect()->route('spatial-layers.features.create', $spatialLayer)
                ->withErrors(['input_type' => $e->getMessage()])->withInput();
        }

        $detectedFields = collect($results)
            ->flatMap(fn (array $result) => array_keys($result['attributes']))
            ->unique()->values()->all();

        $import = LayerImport::create([
            'layer_id' => $spatialLayer->id,
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

        return redirect()->route('spatial-layers.imports.mapping.edit', [$spatialLayer, $import])
            ->with('success', 'File berhasil diunggah ('.count($results).' fitur terdeteksi). Lanjutkan pemetaan kolom.');
    }

    /**
     * Tahap 2a: tampilkan kolom terdeteksi + form pemetaan & metadata dinamis.
     */
    public function editMapping(SpatialLayer $spatialLayer, LayerImport $import)
    {
        $this->authorizeOpdAccess($spatialLayer);
        abort_unless($import->layer_id === $spatialLayer->id && $import->status === 'mapping', 404);

        return view('backend.pages.spatial-layers.imports.mapping', [
            'layer' => $spatialLayer,
            'import' => $import,
            'dynamicAttributes' => $this->activeDynamicAttributesFor($spatialLayer),
        ]);
    }

    /**
     * Tahap 2b: terapkan pemetaan, bangun `properties` final, simpan fitur
     * (mode replace/append, R12), catat `layer_attribute_mappings`, dan
     * selesaikan status impor.
     */
    public function processMapping(Request $request, SpatialLayer $spatialLayer, LayerImport $import)
    {
        $this->authorizeOpdAccess($spatialLayer);
        abort_unless($import->layer_id === $spatialLayer->id && $import->status === 'mapping', 404);

        $dynamicAttributes = $this->activeDynamicAttributesFor($spatialLayer);
        $definitionsByKode = $dynamicAttributes->pluck('metadataDefinition')->keyBy('kode');

        $validated = $request->validate([
            'mapping' => 'array',
            'mapping.*' => 'nullable|string',
        ] + $this->metadataDinamisRules($spatialLayer));

        $metadataDinamis = $validated['metadata_dinamis'] ?? [];
        $mapping = $validated['mapping'] ?? [];
        $gambarPath = $this->storeGambarIfPresent($request);

        $results = $import->log['features'] ?? [];

        DB::transaction(function () use ($results, $mapping, $definitionsByKode, $metadataDinamis, $gambarPath, $spatialLayer, $import) {
            if ($import->import_mode === 'replace') {
                SpatialLayerFeature::where('layer_id', $spatialLayer->id)->delete();
            }

            foreach ($results as $result) {
                $properties = $this->applyMapping($result['attributes'], $mapping);
                $quotedWkt = DB::connection()->getPdo()->quote($result['wkt']);

                SpatialLayerFeature::create([
                    'layer_id' => $spatialLayer->id,
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
        });

        $import->update([
            'status' => 'completed',
            'imported_features' => count($results),
            'failed_features' => 0,
            'log' => [],
            'finished_at' => now(),
        ]);

        $spatialLayer->refreshFeatureCache();

        return redirect()->route('spatial-layers.show', $spatialLayer)
            ->with('success', 'Berhasil menyimpan '.count($results).' Data Spasial dari impor.');
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
     * @return array<int, array{wkt: string, attributes: array}>
     */
    private function importFromShapefile(Request $request, SpatialGeometryBatchImporter $importer): array
    {
        return $importer->fromShapefile($request->file('shp_file'), $request->file('shx_file'), $request->file('dbf_file'));
    }

    /**
     * @return array<int, array{wkt: string, attributes: array}>
     */
    private function importFromKmz(Request $request, SpatialGeometryBatchImporter $importer): array
    {
        return $importer->fromKmz($request->file('kmz_file'));
    }

    private function activeDynamicAttributesFor(SpatialLayer $layer)
    {
        if (! $layer->map_type_id) {
            return collect();
        }

        return MapTypeDynamicAttribute::where('map_type_id', $layer->map_type_id)
            ->where('is_active', true)
            ->with('metadataDefinition')
            ->orderBy('urutan')
            ->get();
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

    private function authorizeOpdAccess(SpatialLayer $layer): void
    {
        $user = Auth::user();

        if ($user?->role?->slug === 'admin-opd' && $layer->opd_id !== $user->opd_id) {
            abort(403, 'Anda tidak memiliki akses ke Layer milik OPD lain.');
        }
    }
}
