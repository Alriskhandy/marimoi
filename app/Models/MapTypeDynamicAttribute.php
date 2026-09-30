<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pivot antara Jenis (map_types) dan katalog global MetadataDefinition
 * (docs/marimoi v2/03_plan/14-penyesuaian-database-jenis-peta.md Bagian 6, Opsi B)
 * — bukan penyimpan definisi lagi (itu tanggung jawab MetadataDefinition), cuma
 * konfigurasi per-Jenis: wajib/opsional, aktif/nonaktif, urutan tampil.
 * Nilai aktual tetap selalu di SpatialLayerFeature::$metadata_dinamis.
 */
class MapTypeDynamicAttribute extends Model
{
    protected $fillable = [
        'map_type_id',
        'metadata_definition_id',
        'is_wajib',
        'is_enabled',
        'urutan',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_wajib' => 'boolean',
            'is_enabled' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function mapType(): BelongsTo
    {
        return $this->belongsTo(MapType::class);
    }

    public function metadataDefinition(): BelongsTo
    {
        return $this->belongsTo(MetadataDefinition::class);
    }
}
