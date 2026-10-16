<?php

namespace App\Http\Controllers;

use App\Models\LayerStyle;
use App\Models\SpatialLayer;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Simbolisasi layer (spec-admin-manajemen-peta.md §5.6, Fase F) — sebelum ini
 * hanya ada SATU style `simple` per layer, ditulis implisit dari field warna/
 * ikon/opacity di form Layer (SpatialLayerController::validated()/_form.blade.php,
 * tetap dipertahankan sebagai cara cepat mengubah style default tanpa pindah
 * halaman). Halaman ini menambah kemampuan `categorized`/`graduated` dan
 * beberapa style per layer dengan satu default, sesuai skema `layer_styles`
 * yang sudah ada sejak migrasi v3.
 *
 * `classification_field` HANYA boleh kode atribut dinamis milik Jenis Peta
 * layer ini (D2) — classified bukan atas kolom bebas, karena nilai fitur yang
 * benar-benar bisa diklasifikasi cuma yang terdaftar di `metadata_definitions`
 * lewat `map_type_dynamic_attributes`.
 */
class LayerStyleController extends Controller
{
    private const STYLE_TYPES = [
        'simple' => 'Simple (satu warna/ikon)',
        'categorized' => 'Categorized (per nilai atribut)',
        'graduated' => 'Graduated (per rentang nilai)',
    ];

    public function index(SpatialLayer $spatialLayer)
    {
        $this->authorizeOpdAccess($spatialLayer);

        return view('backend.pages.spatial-layers.styles.index', [
            'layer' => $spatialLayer,
            'styles' => $spatialLayer->styles()->orderByDesc('is_default')->orderBy('name')->get(),
            'styleTypes' => self::STYLE_TYPES,
            'classificationFields' => $this->classificationFieldsFor($spatialLayer),
            // Custom style per Data Spasial (style_override) dikelola di
            // halaman INI juga, bersama style default — sebelumnya tersebar
            // ke halaman "Kelola Data Spasial" terpisah, lihat
            // SpatialLayerFeatureController::updateStyle().
            'features' => $spatialLayer->features()->orderBy('label')->get(),
        ]);
    }

    public function store(Request $request, SpatialLayer $spatialLayer)
    {
        $this->authorizeOpdAccess($spatialLayer);

        $data = $this->validatedStyle($request, $spatialLayer);

        DB::transaction(function () use ($spatialLayer, $data) {
            $style = $spatialLayer->styles()->create($data['attributes']);
            $this->applyDefault($spatialLayer, $style, $data['is_default']);
        });

        return $this->redirectAfterSave($request, $spatialLayer)->with('success', 'Style berhasil ditambahkan.');
    }

    public function update(Request $request, SpatialLayer $spatialLayer, LayerStyle $style)
    {
        $this->authorizeOpdAccess($spatialLayer);
        abort_unless($style->layer_id === $spatialLayer->id, 404);

        $data = $this->validatedStyle($request, $spatialLayer, $style);

        DB::transaction(function () use ($spatialLayer, $style, $data) {
            $style->update($data['attributes']);
            $this->applyDefault($spatialLayer, $style, $data['is_default']);
        });

        return $this->redirectAfterSave($request, $spatialLayer)->with('success', 'Style berhasil diperbarui.');
    }

    /**
     * Modal "Style" cepat di spatial-layers/show.blade.php (edit style
     * default langsung di halaman detail Layer, tanpa pindah ke halaman
     * manajemen style penuh) mengirim `return_to_show=1` supaya redirect
     * kembali ke situ, bukan ke `spatial-layers.styles.index` seperti
     * biasanya dari halaman manajemen style sendiri.
     */
    private function redirectAfterSave(Request $request, SpatialLayer $spatialLayer)
    {
        if ($request->boolean('return_to_show')) {
            return redirect()->route('spatial-layers.show', $spatialLayer);
        }

        return redirect()->route('spatial-layers.styles.index', $spatialLayer);
    }

    public function destroy(SpatialLayer $spatialLayer, LayerStyle $style)
    {
        $this->authorizeOpdAccess($spatialLayer);
        abort_unless($style->layer_id === $spatialLayer->id, 404);

        if ($spatialLayer->default_style_id === $style->id) {
            return redirect()->route('spatial-layers.styles.index', $spatialLayer)
                ->with('error', 'Style ini sedang jadi default — jadikan style lain default dulu sebelum menghapusnya.');
        }

        $style->delete();

        return redirect()->route('spatial-layers.styles.index', $spatialLayer)->with('success', 'Style berhasil dihapus.');
    }

    /**
     * `is_default` dijaga lewat auto-demote (hanya satu style default per
     * Layer, partial unique index `uq_layer_styles_default`), DAN
     * menyinkronkan `layers.default_style_id` yang jadi FK komposit nyata ke
     * baris ini — bukan cuma kolom boolean dekoratif.
     */
    private function applyDefault(SpatialLayer $layer, LayerStyle $style, bool $isDefault): void
    {
        if (! $isDefault) {
            return;
        }

        LayerStyle::where('layer_id', $layer->id)->where('id', '!=', $style->id)->update(['is_default' => false]);
        $style->update(['is_default' => true]);
        $layer->update(['default_style_id' => $style->id]);
    }

    /**
     * @return array{attributes: array<string, mixed>, is_default: bool}
     */
    private function validatedStyle(Request $request, SpatialLayer $layer, ?LayerStyle $style = null): array
    {
        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('layer_styles', 'name')->where('layer_id', $layer->id)->ignore($style?->id),
            ],
            'style_type' => ['required', Rule::in(array_keys(self::STYLE_TYPES))],
            'classification_field' => [
                'required_if:style_type,categorized,graduated', 'nullable', 'string', 'max:63',
                Rule::in($this->classificationFieldsFor($layer)->pluck('kode')->all()),
            ],
            'is_default' => 'boolean',
            'color' => 'nullable|string|max:25',
            'icon' => 'nullable|string|max:255',
            'is_marker' => 'boolean',
            'opacity' => 'nullable|numeric|min:0|max:1',
            'size' => 'nullable|numeric|min:1|max:100',
            'classes' => 'nullable|array',
            'classes.*.value' => 'nullable|string|max:255',
            'classes.*.min' => 'nullable|numeric',
            'classes.*.max' => 'nullable|numeric',
            'classes.*.color' => 'required|string|max:25',
            'classes.*.label' => 'nullable|string|max:255',
        ], [
            'name.unique' => 'Sudah ada style dengan nama ini di Layer ini.',
            'classification_field.required_if' => 'Pilih atribut yang dipakai untuk klasifikasi.',
        ]);

        $isClassified = in_array($validated['style_type'], ['categorized', 'graduated']);

        $definition = $isClassified
            ? ['field' => $validated['classification_field'], 'classes' => $validated['classes'] ?? []]
            : [
                'color' => $validated['color'] ?? '#2563eb',
                'icon' => $validated['icon'] ?? null,
                'is_marker' => (bool) ($validated['is_marker'] ?? false),
                'opacity' => $validated['opacity'] ?? 1,
                'size' => $validated['size'] ?? 6,
            ];

        $legend = $isClassified
            ? collect($validated['classes'] ?? [])->map(fn (array $class) => [
                'label' => $class['label'] ?? ($class['value'] ?? trim(($class['min'] ?? '').' – '.($class['max'] ?? ''))),
                'color' => $class['color'],
            ])->values()->all()
            : [];

        return [
            'attributes' => [
                'name' => $validated['name'],
                'style_type' => $validated['style_type'],
                'classification_field' => $isClassified ? $validated['classification_field'] : null,
                'definition' => $definition,
                'legend' => $legend,
                'created_by' => Auth::id(),
            ],
            'is_default' => (bool) ($validated['is_default'] ?? false),
        ];
    }

    /**
     * SELALU kosong — konsekuensinya style categorized/graduated tidak punya
     * kandidat field dan view menampilkan peringatannya (lihat
     * styles/index.blade.php), sementara style simple tetap bisa dibuat.
     *
     * Field klasifikasi dulu diambil dari atribut dinamis "Jenis Peta", tapi
     * kolom `layers.map_type_id` dilepas 2026-10-06 (migration
     * drop_map_type_id_and_visibility_from_layers_table) sehingga query ini
     * sudah tidak pernah menemukan baris, lalu modul Jenis Peta beserta
     * tabelnya dihapus 2026-10-10 (migration drop_map_types_tables).
     */
    private function classificationFieldsFor(SpatialLayer $layer): Collection
    {
        return collect();
    }

    private function authorizeOpdAccess(SpatialLayer $layer): void
    {
        $user = Auth::user();

        if ($user?->role?->slug === 'admin-opd' && $layer->opd_id !== $user->opd_id) {
            abort(403, 'Anda tidak memiliki akses ke Layer milik OPD lain.');
        }
    }
}
