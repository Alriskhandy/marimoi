<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Skema v3 §5.5 — tabel fisik `layer_metadata` (nama final, tidak collision
 * dengan tabel lama `spatial_layer_metadata`). PK adalah `layer_id` sendiri
 * (relasi 1:1, bukan `id` terpisah), dan tabel ini tidak punya kolom
 * `created_at` sama sekali (hanya `updated_at`) — lihat `const CREATED_AT`.
 *
 * `keywords` (Postgres `text[]` asli, bukan jsonb) SENGAJA belum di-cast ke
 * array PHP — Laravel `array` cast memakai JSON encode/decode yang tidak
 * cocok dengan sintaks array native Postgres (`{a,b,c}`), dan belum ada UI
 * yang menulis/membaca kolom ini. Tangani konversinya manual saat fitur edit
 * metadata v3 dibangun.
 */
class SpatialLayerMetadata extends Model
{
    public $incrementing = false;

    public $timestamps = true;

    protected $table = 'layer_metadata';

    protected $primaryKey = 'layer_id';

    protected $keyType = 'string';

    const CREATED_AT = null;

    protected $fillable = [
        'layer_id',
        'title',
        'abstract',
        'purpose',
        'topic_category',
        'producer_organization',
        'contact_name',
        'contact_email',
        'contact_phone',
        'data_year',
        'reference_date',
        'date_type',
        'update_frequency',
        'scale_denominator',
        'positional_accuracy',
        'source_srid',
        'administrative_area',
        'lineage',
        'license',
        'use_constraints',
        'extra',
    ];

    protected function casts(): array
    {
        return [
            'reference_date' => 'date',
            'extra' => 'array',
        ];
    }

    public function layer(): BelongsTo
    {
        return $this->belongsTo(SpatialLayer::class, 'layer_id');
    }
}
