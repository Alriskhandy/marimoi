<?php

namespace App\Http\Controllers;

use App\Models\LayerImport;
use App\Models\SpatialLayer;
use App\Support\LayerImportPipeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Impor file (Shapefile/KMZ/KML) ke `spatial_features`, lewat wizard 2 tahap
 * (D.2, spec-admin-manajemen-peta.md §5.5 butir 3): upload() mem-parsing file
 * & mendeteksi kolom, lalu editMapping()/processMapping() membiarkan admin
 * memetakan tiap kolom ke atribut standar (`metadata_definitions`) atau
 * menandainya "simpan apa adanya"/"abaikan", baru setelah itu fitur benar-benar
 * disimpan. Input koordinat manual TIDAK lewat sini — lihat
 * SpatialLayerFeatureController (kolomnya tetap/tidak perlu dipetakan).
 *
 * Logika parsing/pemetaan sesungguhnya diekstrak ke `App\Support\
 * LayerImportPipeline` (2026-10-06) supaya LayerWizardController (impor
 * sebagai tahap 2/3 wizard "Tambah Layer") bisa memakai jalur yang IDENTIK
 * tanpa duplikasi — controller ini sekarang hanya pemanggil tipis yang
 * mempertahankan redirect/otorisasi lama untuk halaman impor mandiri
 * (menambah/mengganti data pada Layer yang SUDAH jadi).
 *
 * Hasil parsing mentah (wkt + attributes per baris) disimpan SEMENTARA di
 * `layer_imports.log` (jsonb, kolom serba-guna karena tidak ada tempat lain
 * untuk menyimpan "pekerjaan dalam proses" lintas request) selama status
 * `mapping` — dibersihkan begitu processMapping() selesai, supaya tidak jadi
 * beban permanen.
 */
class LayerImportController extends Controller
{
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
    public function upload(Request $request, SpatialLayer $spatialLayer, LayerImportPipeline $pipeline)
    {
        $this->authorizeOpdAccess($spatialLayer);

        $import = $pipeline->ingest($request, $spatialLayer);

        if ($import->status === 'failed') {
            return redirect()->route('spatial-layers.features.create', $spatialLayer)
                ->withErrors(['input_type' => $import->error_message])->withInput();
        }

        return redirect()->route('spatial-layers.imports.mapping.edit', [$spatialLayer, $import])
            ->with('success', 'File berhasil diunggah ('.$import->total_features.' fitur terdeteksi). Lanjutkan pemetaan kolom.');
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
            'dynamicAttributes' => collect(),
        ]);
    }

    /**
     * Tahap 2b: terapkan pemetaan, bangun `properties` final, simpan fitur
     * (mode replace/append, R12), catat `layer_attribute_mappings`, dan
     * selesaikan status impor.
     */
    public function processMapping(Request $request, SpatialLayer $spatialLayer, LayerImport $import, LayerImportPipeline $pipeline)
    {
        $this->authorizeOpdAccess($spatialLayer);
        abort_unless($import->layer_id === $spatialLayer->id && $import->status === 'mapping', 404);

        $count = $pipeline->process($request, $spatialLayer, $import);

        return redirect()->route('spatial-layers.show', $spatialLayer)
            ->with('success', 'Berhasil menyimpan '.$count.' Data Spasial dari impor.');
    }

    private function authorizeOpdAccess(SpatialLayer $layer): void
    {
        $user = Auth::user();

        if ($user?->role?->slug === 'admin-opd' && $layer->opd_id !== $user->opd_id) {
            abort(403, 'Anda tidak memiliki akses ke Layer milik OPD lain.');
        }
    }
}
