<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Skema v3 §5.11 — tabel fisik `spatial_features_v3` (nama final
 * `spatial_features` menyusul di Fase 6 cutover). `gambar`/`created_by`
 * adalah kolom tambahan non-dokumen (lihat migration
 * add_gambar_and_created_by_to_spatial_features_v3_table) — fitur foto
 * per-fitur yang sudah berjalan di admin UI, dipertahankan.
 *
 * Relasi ke SpatialLayerFeatureIntervention (v2) SENGAJA dihapus di sini —
 * tabel itu belum punya rekan v3 dan tidak dipakai di mana pun selain
 * definisi relasinya sendiri (dead code).
 */
class SpatialLayerFeature extends Model
{
    protected $table = 'spatial_features';

    protected $fillable = [
        'layer_id',
        'layer_import_id',
        'source_fid',
        'geom',
        'properties',
        'style_override',
        'label',
        'region_id',
        'gambar',
        'created_by',
        'legacy_data_spatial_id',
        'legacy_spatial_layer_feature_id',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'style_override' => 'array',
        ];
    }

    public function layer(): BelongsTo
    {
        return $this->belongsTo(SpatialLayer::class, 'layer_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(AdministrativeRegion::class, 'region_id');
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(LayerImport::class, 'layer_import_id');
    }
}
