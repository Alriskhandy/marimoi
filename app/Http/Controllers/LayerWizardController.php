<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesLayerAccess;
use App\Models\LayerImport;
use App\Models\LayerType;
use App\Models\Opd;
use App\Models\SpatialLayer;
use App\Models\SpatialLayerMetadata;
use App\Support\LayerImportPipeline;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Wizard "Tambah Layer" 4 tahap (2026-10-06, plan rippling-frolicking-ladybug):
 * Informasi Layer -> Impor Data -> Mapping Atribut -> Review & Publish.
 *
 * Checkpoint-nya adalah kolom `layers.wizard_step` (1-4, NULL kalau wizard
 * sudah tuntas/bukan draft wizard). Tahap 1 membuat baris Layer `draft` +
 * style default sekaligus (pola sama SpatialLayerController::store()) supaya
 * sejak saat itu Layer sudah punya identitas permanen — kalau tahap impor
 * gagal (file rusak), hanya `wizard_step` yang tetap di posisi semula;
 * Layer dan style-nya TIDAK dibuat ulang.
 *
 * Jenis Layer di tahap 1 dibatasi ke yang `stores_features = true` (vektor)
 * karena Layer raster/layanan (WMS/WMTS/XYZ/ArcGIS/COG) direncanakan dihapus
 * terpisah dari sesi ini — wizard ini sengaja tidak menangani jalur Source
 * eksternal supaya tidak membangun sesuatu yang akan dibongkar lagi.
 */
class LayerWizardController extends Controller
{
    use HandlesLayerAccess;

    public function create()
    {
        return view('backend.pages.spatial-layers.create', [
            'layer' => null,
            'currentStep' => 1,
            'wizardStep' => 1,
        ] + $this->infoStepData(null));
    }

    public function store(Request $request)
    {
        $validated = $this->validateInfo($request);

        $layer = DB::transaction(function () use ($validated) {
            $layer = SpatialLayer::create($validated['layer'] + [
                'status' => 'draft',
                'wizard_step' => 2,
            ]);

            $style = $layer->styles()->create($validated['style'] + [
                'name' => 'Default',
                'style_type' => 'simple',
                'is_default' => true,
            ]);

            $layer->update(['default_style_id' => $style->id]);

            $this->saveMetadataFields($layer, $validated['metadata']);

            return $layer;
        });

        return redirect()->route('spatial-layers.wizard', $layer)
            ->with('success', 'Informasi Layer tersimpan. Lanjutkan ke tahap impor data.');
    }

    /**
     * Resume: render wizard di tahap tersimpan (`wizard_step`), atau di tahap
     * lebih awal lewat `?step=N` (mis. user menekan "Kembali" dari tahap 3
     * untuk melihat/mengubah Informasi Layer lagi) — tidak pernah melompat
     * ke tahap yang belum tercapai.
     */
    public function wizard(Request $request, SpatialLayer $spatialLayer)
    {
        $this->authorizeOpdAccess($spatialLayer);

        if ($spatialLayer->wizard_step === null) {
            return redirect()->route('spatial-layers.show', $spatialLayer);
        }

        $requestedStep = (int) $request->query('step', $spatialLayer->wizard_step);
        $step = max(1, min($requestedStep, $spatialLayer->wizard_step));

        if ($step === 3) {
            $pendingImport = $this->pendingMappingImport($spatialLayer);

            // wizard_step bisa saja sudah 3+ tapi tidak ada import yang
            // masih berstatus 'mapping' (sudah diproses lewat ?step=3 di
            // request lain, atau belum pernah upload) — mundur ke tahap
            // impor daripada merender form mapping tanpa data.
            if (! $pendingImport) {
                $step = 2;
            }
        }

        return $this->renderStep($spatialLayer, $step);
    }

    /**
     * Simpan ulang tahap 1 (dipanggil saat user kembali ke tahap Informasi
     * Layer dari tahap manapun). `wizard_step` TIDAK dimundurkan di sini.
     */
    public function saveInfo(Request $request, SpatialLayer $spatialLayer)
    {
        $this->authorizeOpdAccess($spatialLayer);

        $validated = $this->validateInfo($request, $spatialLayer);

        DB::transaction(function () use ($spatialLayer, $validated) {
            $spatialLayer->update($validated['layer']);

            $style = $spatialLayer->styles()->where('is_default', true)->first();

            if ($style) {
                // Hanya `definition` yang disentuh — JANGAN timpa
                // `style_type`/`classification_field` kalau style default
                // Layer ini sudah diubah jadi categorized/graduated lewat
                // halaman /styles (jebakan yang sama ada di
                // SpatialLayerController::update()).
                $style->update($validated['style']);
            } else {
                $style = $spatialLayer->styles()->create($validated['style'] + [
                    'name' => 'Default',
                    'style_type' => 'simple',
                    'is_default' => true,
                ]);
                $spatialLayer->update(['default_style_id' => $style->id]);
            }

            $this->saveMetadataFields($spatialLayer, $validated['metadata']);
        });

        return redirect()->route('spatial-layers.wizard', $spatialLayer)
            ->with('success', 'Informasi Layer diperbarui.');
    }

    /**
     * Tahap 2: upload & parsing file. Kegagalan parsing TIDAK memundurkan
     * atau menghapus apa pun — `wizard_step` tetap di posisi semula dan user
     * mendarat kembali di tahap ini dengan pesan error (requirement utama
     * wizard ini: file rusak tidak memaksa mengulang dari tahap 1).
     */
    public function saveImport(Request $request, SpatialLayer $spatialLayer, LayerImportPipeline $pipeline)
    {
        $this->authorizeOpdAccess($spatialLayer);
        abort_if($spatialLayer->wizard_step === null, 404);

        // Koordinat manual tidak punya kolom untuk dipetakan (nama kolom
        // hasil parsing sudah tetap) — tahap 3 (Mapping Atribut) DILEWATI
        // sepenuhnya, langsung ke tahap 4. ValidationException dari
        // ingestCoordinates() (termasuk "tidak ada koordinat valid")
        // otomatis redirect balik ke tahap 2 dengan error, wizard_step tetap.
        if ($request->input('input_type') === 'coordinates') {
            $count = $pipeline->ingestCoordinates($request, $spatialLayer);

            if ($spatialLayer->wizard_step < 4) {
                $spatialLayer->update(['wizard_step' => 4]);
            }

            return redirect()->route('spatial-layers.wizard', $spatialLayer)
                ->with('success', 'Berhasil menyimpan '.$count.' Data Spasial dari koordinat.');
        }

        $import = $pipeline->ingest($request, $spatialLayer);

        if ($import->status === 'failed') {
            return redirect()->route('spatial-layers.wizard', [$spatialLayer, 'step' => 2])
                ->withErrors(['input_type' => $import->error_message])->withInput();
        }

        if ($spatialLayer->wizard_step < 3) {
            $spatialLayer->update(['wizard_step' => 3]);
        }

        return redirect()->route('spatial-layers.wizard', $spatialLayer)
            ->with('success', 'File berhasil diunggah ('.$import->total_features.' fitur terdeteksi). Lanjutkan pemetaan kolom.');
    }

    /**
     * Tahap 3: terapkan pemetaan kolom & simpan fitur. Import yang diproses
     * selalu import terbaru berstatus `mapping` milik Layer ini — wizard
     * hanya mengizinkan satu import aktif per Layer dalam satu waktu.
     */
    public function saveMapping(Request $request, SpatialLayer $spatialLayer, LayerImportPipeline $pipeline)
    {
        $this->authorizeOpdAccess($spatialLayer);

        $import = $this->pendingMappingImport($spatialLayer);
        abort_if(! $import, 404);

        $count = $pipeline->process($request, $spatialLayer, $import);

        if ($spatialLayer->wizard_step < 4) {
            $spatialLayer->update(['wizard_step' => 4]);
        }

        return redirect()->route('spatial-layers.wizard', $spatialLayer)
            ->with('success', 'Berhasil menyimpan '.$count.' Data Spasial dari impor.');
    }

    /**
     * Tahap 4: selesaikan wizard. Publish opsional dan hanya untuk pemegang
     * `spatial-layers.publish` — prasyaratnya sama dengan
     * SpatialLayerController::updateStatus() (fitur + style default wajib
     * ada). Tanpa publish, Layer tetap `draft` seperti biasa.
     */
    public function finish(Request $request, SpatialLayer $spatialLayer)
    {
        $this->authorizeOpdAccess($spatialLayer);
        abort_if($spatialLayer->wizard_step === null, 404);

        $validated = $request->validate([
            'publish' => 'nullable|boolean',
            'thumbnail' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:10240',
        ]);
        $wantsPublish = (bool) ($validated['publish'] ?? false);
        $canPublish = $wantsPublish && (bool) Auth::user()?->can('spatial-layers.publish');

        $this->storeThumbnailIfPresent($request, $spatialLayer);

        if ($canPublish) {
            if (! $spatialLayer->features()->exists()) {
                throw ValidationException::withMessages([
                    'publish' => 'Layer belum punya Data Spasial, tidak dapat dipublikasikan.',
                ]);
            }

            if (! $spatialLayer->default_style_id) {
                throw ValidationException::withMessages([
                    'publish' => 'Layer belum punya style default, tidak dapat dipublikasikan.',
                ]);
            }

            $spatialLayer->update([
                'status' => 'published',
                'published_at' => $spatialLayer->published_at ?? now(),
                'wizard_step' => null,
            ]);

            return redirect()->route('spatial-layers.show', $spatialLayer)
                ->with('success', 'Layer berhasil dibuat dan dipublikasikan.');
        }

        $spatialLayer->update(['wizard_step' => null]);

        return redirect()->route('spatial-layers.show', $spatialLayer)
            ->with('success', 'Layer berhasil dibuat.');
    }

    private function pendingMappingImport(SpatialLayer $spatialLayer): ?LayerImport
    {
        return LayerImport::where('layer_id', $spatialLayer->id)
            ->where('status', 'mapping')
            ->latest('created_at')
            ->first();
    }

    /**
     * Tahap 4: gambar preview Layer opsional. Tidak ada kolom khusus untuk
     * ini di `layers` — disimpan di `definition.thumbnail` milik style
     * default (JSON yang sama yang sudah menyimpan color/icon/opacity),
     * bukan migration baru. Kalau user memilih preview bawaan (digenerate
     * dari warna+jenis geometri di sisi klien, lihat _wizard-step-4-review),
     * tidak ada apa pun yang perlu disimpan — itu dibangun ulang dari data
     * Layer yang sudah ada setiap kali ditampilkan.
     */
    private function storeThumbnailIfPresent(Request $request, SpatialLayer $spatialLayer): void
    {
        if (! $request->hasFile('thumbnail')) {
            return;
        }

        $style = $spatialLayer->defaultStyle ?? $spatialLayer->styles()->where('is_default', true)->first();

        if (! $style) {
            return;
        }

        $path = $request->file('thumbnail')->store('layer-thumbnails', 'public');

        $style->update(['definition' => array_merge($style->definition ?? [], ['thumbnail' => $path])]);
    }

    private function renderStep(SpatialLayer $spatialLayer, int $step)
    {
        $data = [
            'layer' => $spatialLayer,
            'currentStep' => $step,
            'wizardStep' => $spatialLayer->wizard_step,
        ];

        $data += match ($step) {
            1 => $this->infoStepData($spatialLayer),
            2 => $this->importStepData($spatialLayer),
            3 => $this->mappingStepData($spatialLayer),
            default => $this->reviewStepData($spatialLayer),
        };

        return view('backend.pages.spatial-layers.create', $data);
    }

    /**
     * @return array<string, mixed>
     */
    private function infoStepData(?SpatialLayer $layer): array
    {
        [$categoryOptions, $categoryNodeOptions] = $this->categoryPickerOptions();

        return [
            'layerTypes' => LayerType::where('stores_features', true)->orderBy('name')->get(),
            'opds' => $this->canAssignOpd() ? Opd::orderBy('name')->get(['id', 'name', 'singkatan']) : collect(),
            'categoryOptions' => $categoryOptions,
            'categoryNodeOptions' => $categoryNodeOptions,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function importStepData(SpatialLayer $layer): array
    {
        return [
            'failedImports' => $layer->imports()->where('status', 'failed')->latest('created_at')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mappingStepData(SpatialLayer $layer): array
    {
        return [
            'import' => $this->pendingMappingImport($layer),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function reviewStepData(SpatialLayer $layer): array
    {
        $layer->load(['layerType', 'categoryNode', 'opd', 'defaultStyle']);

        return [
            'featureCount' => $layer->features()->count(),
            'latestImport' => $layer->imports()->latest('created_at')->first(),
            'canPublish' => (bool) Auth::user()?->can('spatial-layers.publish'),
        ];
    }

    /**
     * @return array{0: Collection, 1: Collection}
     */
    private function categoryPickerOptions(): array
    {
        $categoryOptions = DB::table('categories_v3')->orderBy('name')->get(['id', 'name']);

        $categoryNodeOptions = DB::table('category_nodes')
            ->orderBy('depth')
            ->orderBy('name')
            ->get(['id', 'name', 'category_id', 'depth']);

        return [$categoryOptions, $categoryNodeOptions];
    }

    /**
     * @return array{layer: array<string, mixed>, style: array<string, mixed>}
     */
    private function validateInfo(Request $request, ?SpatialLayer $layer = null): array
    {
        $validated = $request->validate([
            'layer_type_id' => ['required', Rule::exists('layer_types', 'id')->where('stores_features', true)],
            'opd_id' => 'nullable|exists:opd,id',
            'category_id' => ['required', 'uuid', 'exists:categories_v3,id'],
            'category_node_id' => ['nullable', 'uuid', 'exists:category_nodes,id'],
            'name' => 'required|string|max:255',
            'short_description' => 'nullable|string',
            'color' => 'nullable|string|max:25',
            'icon' => 'nullable|string|max:255',
            'default_opacity' => 'nullable|numeric|min:0|max:1',
            'is_marker' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'data_year' => 'nullable|integer|min:1900|max:2100',
            'sumber_data' => 'nullable|string|max:255',
        ]);

        $this->assertCategoryNodeBelongsToCategory($validated['category_node_id'] ?? null, $validated['category_id']);

        $opdId = $this->canAssignOpd() ? ($validated['opd_id'] ?? $layer?->opd_id) : $this->currentOpdId();
        $opacity = $validated['default_opacity'] ?? ($layer?->default_opacity ?? 1);

        return [
            'layer' => [
                'category_id' => $validated['category_id'],
                'category_node_id' => $validated['category_node_id'] ?? null,
                'opd_id' => $opdId,
                'layer_type_id' => $validated['layer_type_id'],
                'code' => $layer?->code ?? $this->uniqueCode(),
                'name' => $validated['name'],
                'slug' => $layer?->slug ?? $this->uniqueSlug($validated['name']),
                'short_description' => $validated['short_description'] ?? null,
                'default_opacity' => $opacity,
                'sort_order' => $validated['sort_order'] ?? $layer?->sort_order ?? 0,
            ],
            'style' => [
                'definition' => [
                    'color' => $validated['color'] ?? '#2563eb',
                    'icon' => $validated['icon'] ?? null,
                    'is_marker' => (bool) ($validated['is_marker'] ?? false),
                    'opacity' => $opacity,
                ],
            ],
            'metadata' => [
                'data_year' => $validated['data_year'] ?? null,
                'sumber_data' => $validated['sumber_data'] ?? null,
            ],
        ];
    }

    /**
     * Tahap 1 juga menulis `data_year`/`sumber_data` ke `layer_metadata`
     * (1:1, dipakai halaman Metadata lengkap di tempat terpisah) supaya dua
     * field paling sering ditanya Admin OPD sejak awal tidak menunggu
     * sampai mereka sempat membuka halaman Metadata.
     */
    private function saveMetadataFields(SpatialLayer $layer, array $fields): void
    {
        $metadata = SpatialLayerMetadata::firstOrNew(['layer_id' => $layer->id]);
        $metadata->fill($fields);
        $metadata->save();
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (SpatialLayer::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    private function uniqueCode(): string
    {
        do {
            $code = 'layer-'.Str::lower(Str::random(10));
        } while (SpatialLayer::where('code', $code)->exists());

        return $code;
    }
}
