<?php

namespace App\Http\Controllers;

use App\Models\MapType;
use App\Models\MapTypeDynamicAttribute;
use App\Models\Opd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Jenis Layer & Data (docs/marimoi v2/04_implementation/12-implementasi-perbaikan-pemetaan.md
 * Bagian 3.1) — CRUD map_types diperluas dengan Metadata Utama (wajib) dan skema
 * Metadata Dinamis (referensi/panduan, bukan penyimpan nilai — lihat Keputusan #2).
 */
class MapTypeController extends Controller
{
    public function index()
    {
        $mapTypes = MapType::withCount('spatialLayers')->orderBy('urutan')->get();

        return view('backend.pages.map-types.index', compact('mapTypes'));
    }

    public function create()
    {
        $opdOptions = Opd::orderBy('name')->get(['id', 'name', 'singkatan']);
        $placeholderAttributes = MapTypeDynamicAttribute::PLACEHOLDER_ATTRIBUTES;

        return view('backend.pages.map-types.create', compact('opdOptions', 'placeholderAttributes'));
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

    public function edit(MapType $mapType)
    {
        $mapType->load('dynamicAttributes');
        $opdOptions = Opd::orderBy('name')->get(['id', 'name', 'singkatan']);
        $placeholderAttributes = MapTypeDynamicAttribute::PLACEHOLDER_ATTRIBUTES;

        return view('backend.pages.map-types.edit', compact('mapType', 'opdOptions', 'placeholderAttributes'));
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
     * Sinkronkan skema Metadata Dinamis (bukan nilai — lihat Keputusan #2). Baris
     * yang tidak lagi dikirim dihapus, yang dikirim dengan id di-update, tanpa id
     * dibuat baru.
     */
    private function syncDynamicAttributes(MapType $mapType, array $rows): void
    {
        $keptIds = [];

        foreach ($rows as $row) {
            if (blank($row['kode_atribut'] ?? null) || blank($row['label'] ?? null)) {
                continue;
            }

            $attribute = MapTypeDynamicAttribute::updateOrCreate(
                ['map_type_id' => $mapType->id, 'kode_atribut' => $row['kode_atribut']],
                [
                    'tipe' => $row['tipe'] ?? MapTypeDynamicAttribute::TIPE_CUSTOM,
                    'label' => $row['label'],
                    'satuan' => $row['satuan'] ?? null,
                    'is_wajib' => (bool) ($row['is_wajib'] ?? false),
                    'urutan' => (int) ($row['urutan'] ?? 0),
                    'is_active' => (bool) ($row['is_active'] ?? true),
                ]
            );

            $keptIds[] = $attribute->id;
        }

        $mapType->dynamicAttributes()->whereNotIn('id', $keptIds)->delete();
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
            'icon' => 'nullable|string|max:255',
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
