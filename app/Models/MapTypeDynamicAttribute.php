<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Skema/definisi atribut dinamis per Jenis (docs/marimoi v2/04_implementation/
 * 12-implementasi-perbaikan-pemetaan.md Bagian 1.2/2) — bukan penyimpan nilai.
 * Nilai selalu diisi di SpatialLayerFeature::$metadata_dinamis.
 */
class MapTypeDynamicAttribute extends Model
{
    public const TIPE_PLACEHOLDER = 'placeholder';

    public const TIPE_CUSTOM = 'custom';

    public const PLACEHOLDER_ATTRIBUTES = [
        'pagu' => ['label' => 'Pagu', 'satuan' => 'Rp'],
        'realisasi_anggaran' => ['label' => 'Realisasi Anggaran', 'satuan' => 'Rp'],
        'realisasi_fisik' => ['label' => 'Realisasi Fisik', 'satuan' => '%'],
        'status' => ['label' => 'Status', 'satuan' => null],
    ];

    protected $fillable = [
        'map_type_id',
        'tipe',
        'kode_atribut',
        'label',
        'satuan',
        'is_wajib',
        'urutan',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_wajib' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function mapType(): BelongsTo
    {
        return $this->belongsTo(MapType::class);
    }
}
