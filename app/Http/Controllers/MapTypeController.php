<?php

namespace App\Http\Controllers;

use App\Models\MapType;
use App\Models\MapTypeDynamicAttribute;
use App\Models\MetadataDefinition;
use App\Models\Opd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Jenis Layer & Data (docs/marimoi v2/04_implementation/12-implementasi-perbaikan-pemetaan.md
 * Bagian 3.1) — CRUD map_types diperluas dengan Metadata Utama (wajib) dan skema
 * Metadata Dinamis (referensi/panduan, bukan penyimpan nilai — lihat Keputusan #2).
 * Metadata Dinamis sekarang lewat katalog global `MetadataDefinition`, bukan
 * definisi yang menyatu di baris pivot (docs/marimoi v2/03_plan/
 * 14-penyesuaian-database-jenis-peta.md Bagian 6, Opsi B).
 */
class MapTypeController extends Controller
{
    public function index()
    {
        $mapTypes = MapType::withCount([
            'spatialLayers',
            'dynamicAttributes as atribut_utama_count' => fn ($query) => $query->where('is_active', true)
                ->whereHas('metadataDefinition', fn ($q) => $q->where('is_system', true)),
            'dynamicAttributes as atribut_tambahan_count' => fn ($query) => $query->where('is_active', true)
                ->whereHas('metadataDefinition', fn ($q) => $q->where('is_system', false)),
        ])->orderBy('urutan')->get();

        return view('backend.pages.map-types.index', compact('mapTypes'));
    }

    public function create()
    {
        $opdOptions = Opd::orderBy('name')->get(['id', 'name', 'singkatan']);

        return view('backend.pages.map-types.create', compact('opdOptions'));
    }

    public function store(Request $request)
    {
        $validator = $this->validator($request);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $mapType = DB::transaction(function () use ($validator, $request) {
            $mapType = MapType::create($validator->validated());
            $this->syncDynamicAttributes($mapType, $request->input('dynamic_attributes', []));

            return $mapType;
        });

        return redirect()->route('map-types.edit', $mapType)->with('success', 'Jenis peta berhasil dibuat');
    }

    /**
     * Halaman edit sekaligus jadi halaman detail (bukan dua halaman terpisah) —
     * form ubah langsung disertai ringkasan Layer yang memakai Jenis ini.
     */
    public function edit(MapType $mapType)
    {
        $mapType->load('dynamicAttributes.metadataDefinition');
        $mapType->loadCount('spatialLayers');
        $mapType->load(['spatialLayers' => fn ($query) => $query->orderBy('name')]);
        $opdOptions = Opd::orderBy('name')->get(['id', 'name', 'singkatan']);

        return view('backend.pages.map-types.edit', compact('mapType', 'opdOptions'));
    }

    public function update(Request $request, MapType $mapType)
    {
        $validator = $this->validator($request, $mapType->id);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::transaction(function () use ($validator, $request, $mapType) {
            $mapType->update($validator->validated());
            $this->syncDynamicAttributes($mapType, $request->input('dynamic_attributes', []));
        });

        return redirect()->route('map-types.edit', $mapType)->with('success', 'Jenis peta berhasil diperbarui');
    }

    public function destroy(MapType $mapType)
    {
        if ($mapType->spatialLayers()->exists()) {
            return redirect()->back()->with('error', 'Jenis peta tidak dapat dihapus karena masih dipakai oleh Layer.');
        }

        $mapType->delete();

        return redirect()->route('map-types.index')->with('success', 'Jenis peta berhasil dihapus');
    }

    /**
     * Sinkronkan pivot Metadata Dinamis (bukan nilai — lihat Keputusan #2). Baris
     * yang tidak lagi dikirim dihapus. Tiap baris payload berisi SALAH SATU:
     * - `metadata_definition_id` — definisi existing dari katalog yang dipilih user.
     * - `kode`/`label`/`satuan`/`data_type` — definisi baru, di-`firstOrCreate` ke
     *   `metadata_definitions` dulu (kalau `kode` sudah dipakai definisi lain,
     *   otomatis reuse definisi itu — sesuai tujuan katalog global Opsi B).
     */
    private function syncDynamicAttributes(MapType $mapType, array $rows): void
    {
        $keptIds = [];

        foreach ($rows as $row) {
            $definition = $this->resolveDefinition($row);

            if (! $definition) {
                continue;
            }

            $attribute = MapTypeDynamicAttribute::updateOrCreate(
                ['map_type_id' => $mapType->id, 'metadata_definition_id' => $definition->id],
                [
                    'is_wajib' => (bool) ($row['is_wajib'] ?? false),
                    'is_enabled' => true,
                    'urutan' => (int) ($row['urutan'] ?? 0),
                    'is_active' => (bool) ($row['is_active'] ?? true),
                ]
            );

            $keptIds[] = $attribute->id;
        }

        $mapType->dynamicAttributes()->whereNotIn('id', $keptIds)->delete();
    }

    private function resolveDefinition(array $row): ?MetadataDefinition
    {
        if (! blank($row['metadata_definition_id'] ?? null)) {
            return MetadataDefinition::find($row['metadata_definition_id']);
        }

        if (blank($row['kode'] ?? null) || blank($row['label'] ?? null)) {
            return null;
        }

        $dataType = in_array($row['data_type'] ?? null, MetadataDefinition::DATA_TYPES, true)
            ? $row['data_type']
            : MetadataDefinition::TYPE_TEXT;

        $opsi = $dataType === MetadataDefinition::TYPE_SELECT
            ? array_values(array_filter(array_map('trim', explode("\n", (string) ($row['opsi'] ?? '')))))
            : null;

        return MetadataDefinition::firstOrCreate(
            ['kode' => $row['kode']],
            [
                'label' => $row['label'],
                'satuan' => $row['satuan'] ?? null,
                'data_type' => $dataType,
                'opsi' => $opsi,
                'is_system' => false,
                'is_filterable' => (bool) ($row['is_filterable'] ?? false),
                'created_by' => auth()->id(),
            ]
        );
    }

    private function validator(Request $request, ?int $ignoreId = null)
    {
        return Validator::make($request->all(), [
            'slug' => [
                'required', 'string', 'max:255', 'regex:/^[a-z0-9_]+$/',
                Rule::unique('map_types', 'slug')->ignore($ignoreId),
            ],
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'sumber_data' => 'required|string|max:255',
            'opd_penanggung_jawab_id' => 'required|exists:opd,id',
            'tanggal_data' => 'required|date',
            'urutan' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ], [
            'slug.regex' => 'Slug hanya boleh huruf kecil, angka, dan underscore',
            'slug.unique' => 'Slug sudah dipakai jenis peta lain',
            'sumber_data.required' => 'Sumber Data wajib diisi (Metadata Utama)',
            'opd_penanggung_jawab_id.required' => 'OPD Penanggung Jawab wajib dipilih (Metadata Utama)',
            'tanggal_data.required' => 'Tahun/Tanggal Data wajib diisi (Metadata Utama)',
        ]);
    }
}
