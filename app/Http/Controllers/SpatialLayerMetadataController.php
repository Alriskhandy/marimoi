<?php

namespace App\Http\Controllers;

use App\Models\SpatialLayer;
use App\Models\SpatialLayerMetadata;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Metadata layer skema v3 (`layer_metadata`). Sejak Fase C (plan
 * mellow-weaving-eclipse, implementasi spec-admin-manajemen-peta.md §5.3)
 * diakses langsung lewat `SpatialLayer` (route-model-binding), BUKAN lagi
 * lewat id kategori lama — `editByCategory()`/`updateByCategory()` di bawah
 * murni jembatan redirect supaya bookmark admin lama (`categories/{id}/metadata`)
 * tidak mati, bukan jalur utama lagi.
 *
 * `keywords` adalah kolom Postgres `text[]` asli (bukan jsonb) — PDO tidak
 * bisa bind array PHP ke tipe ini secara native, jadi ditulis/dibaca lewat
 * raw query dengan literal `{a,b,c}` (lihat keywordsToPgArray()/
 * keywordsFromPgArray()). Kata kunci yang mengandung koma/kurung kurawal
 * tidak didukung — cukup untuk kata kunci pendek, bukan kalimat bebas.
 */
class SpatialLayerMetadataController extends Controller
{
    private const UPDATE_FREQUENCIES = [
        'once' => 'Sekali saja',
        'annually' => 'Tahunan',
        'semiannually' => 'Semester',
        'quarterly' => 'Triwulan',
        'monthly' => 'Bulanan',
        'irregular' => 'Tidak tentu',
    ];

    private const DATE_TYPES = [
        'creation' => 'Pembuatan',
        'publication' => 'Publikasi',
        'revision' => 'Revisi',
    ];

    public function edit(SpatialLayer $spatialLayer)
    {
        $this->authorizeOpdAccess($spatialLayer);

        $metadata = $spatialLayer->metadata ?? new SpatialLayerMetadata(['layer_id' => $spatialLayer->id]);
        $keywords = $this->keywordsFromPgArray(
            DB::table('layer_metadata')->where('layer_id', $spatialLayer->id)->value('keywords')
        );
        $updateFrequencies = self::UPDATE_FREQUENCIES;
        $dateTypes = self::DATE_TYPES;

        return view('backend.pages.spatial-layers.metadata', [
            'layer' => $spatialLayer,
            'metadata' => $metadata,
            'keywords' => $keywords,
            'updateFrequencies' => $updateFrequencies,
            'dateTypes' => $dateTypes,
        ]);
    }

    public function update(Request $request, SpatialLayer $spatialLayer)
    {
        $this->authorizeOpdAccess($spatialLayer);

        $this->saveMetadata($request, $spatialLayer);

        return redirect()->route('spatial-layers.metadata.edit', $spatialLayer)->with('success', 'Metadata layer berhasil disimpan.');
    }

    /**
     * @deprecated Jembatan redirect — lihat docblock kelas.
     */
    public function editByCategory(int $id)
    {
        $layer = SpatialLayer::where('legacy_category_id', $id)->first();

        if (! $layer) {
            return redirect()->route('categories.index')
                ->with('error', 'Kategori ini belum tersinkron ke Layer (skema baru) — metadata belum bisa diisi.');
        }

        return redirect()->route('spatial-layers.metadata.edit', $layer);
    }

    /**
     * @deprecated Jembatan redirect — lihat docblock kelas. Tetap menyimpan
     * data (form lama masih mengirim PUT ke sini), lalu redirect ke halaman
     * metadata Layer yang baru.
     */
    public function updateByCategory(Request $request, int $id)
    {
        $layer = SpatialLayer::where('legacy_category_id', $id)->first();

        if (! $layer) {
            return redirect()->route('categories.index')
                ->with('error', 'Kategori ini belum tersinkron ke Layer (skema baru) — metadata belum bisa diisi.');
        }

        $this->saveMetadata($request, $layer);

        return redirect()->route('spatial-layers.metadata.edit', $layer)->with('success', 'Metadata layer berhasil disimpan.');
    }

    private function saveMetadata(Request $request, SpatialLayer $layer): void
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:300',
            'abstract' => 'nullable|string',
            'purpose' => 'nullable|string',
            'topic_category' => 'nullable|string|max:60',
            'producer_organization' => 'nullable|string|max:255',
            'sumber_data' => 'nullable|string|max:255',
            'contact_name' => 'nullable|string|max:150',
            'contact_email' => 'nullable|email|max:150',
            'contact_phone' => 'nullable|string|max:40',
            'source_url' => 'nullable|url',
            'license' => 'nullable|string|max:100',
            'attribution' => 'nullable|string',
            'update_frequency' => ['nullable', Rule::in(array_keys(self::UPDATE_FREQUENCIES))],
            'data_year' => 'nullable|integer|min:1900|max:2100',
            'reference_date' => 'nullable|date',
            'date_type' => ['nullable', Rule::in(array_keys(self::DATE_TYPES))],
            'scale_denominator' => 'nullable|integer|min:1',
            'positional_accuracy' => 'nullable|string|max:100',
            'administrative_area' => 'nullable|string|max:200',
            'lineage' => 'nullable|string',
            'use_constraints' => 'nullable|string',
            'keywords' => 'nullable|string|max:1000',
        ]);

        // Field yang tidak dikirim di request sama sekali TIDAK ikut ditimpa
        // (bukan otomatis jadi null) — hanya key yang benar-benar ada di
        // $validated yang di-set, sisanya tetap seperti nilai lama.
        $metadata = SpatialLayerMetadata::firstOrNew(['layer_id' => $layer->id]);

        foreach ([
            'title', 'abstract', 'purpose', 'topic_category', 'producer_organization', 'sumber_data',
            'contact_name', 'contact_email', 'contact_phone', 'license', 'update_frequency',
            'data_year', 'reference_date', 'date_type', 'scale_denominator', 'positional_accuracy',
            'administrative_area', 'lineage', 'use_constraints',
        ] as $key) {
            if (array_key_exists($key, $validated)) {
                $metadata->{$key} = $validated[$key];
            }
        }

        if (array_key_exists('source_url', $validated) || array_key_exists('attribution', $validated)) {
            $extra = $metadata->extra ?? [];
            if (array_key_exists('source_url', $validated)) {
                $extra['source_url'] = $validated['source_url'];
            }
            if (array_key_exists('attribution', $validated)) {
                $extra['attribution'] = $validated['attribution'];
            }
            $metadata->extra = $extra;
        }

        $metadata->save();

        if (array_key_exists('keywords', $validated)) {
            $keywords = array_filter(array_map('trim', explode(',', (string) $validated['keywords'])));
            DB::statement('UPDATE layer_metadata SET keywords = ?::text[] WHERE layer_id = ?', [
                $this->keywordsToPgArray($keywords),
                $layer->id,
            ]);
        }
    }

    private function keywordsToPgArray(array $values): string
    {
        return '{'.implode(',', $values).'}';
    }

    /**
     * @return array<int, string>
     */
    private function keywordsFromPgArray(?string $literal): array
    {
        if (! $literal || $literal === '{}') {
            return [];
        }

        return array_values(array_filter(explode(',', trim($literal, '{}'))));
    }

    private function authorizeOpdAccess(SpatialLayer $layer): void
    {
        $user = Auth::user();

        if ($user?->role?->slug === 'admin-opd' && $layer->opd_id !== $user->opd_id) {
            abort(403, 'Anda tidak memiliki akses ke Layer milik OPD lain.');
        }
    }
}
