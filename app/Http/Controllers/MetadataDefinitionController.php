<?php

namespace App\Http\Controllers;

use App\Models\MetadataDefinition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Katalog global MetadataDefinition (docs/marimoi v2/03_plan/
 * 14-penyesuaian-database-jenis-peta.md Bagian 6 Tahap 5.4, Opsi B) — endpoint
 * search dipakai UI form Jenis Peta untuk "Pilih dari katalog" (bukan CRUD
 * mandiri, katalog cuma diakses lewat form Jenis Peta untuk saat ini — lihat
 * keputusan Bagian 7 poin 7).
 */
class MetadataDefinitionController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->get('q', ''));

        $definitions = MetadataDefinition::query()
            ->when($query !== '', fn ($q) => $q->where(function ($q) use ($query) {
                $q->where('kode', 'ILIKE', "%{$query}%")->orWhere('label', 'ILIKE', "%{$query}%");
            }))
            ->orderBy('is_system', 'desc')
            ->orderBy('label')
            ->limit(20)
            ->get(['id', 'kode', 'label', 'satuan', 'data_type', 'is_system']);

        return response()->json($definitions);
    }
}
