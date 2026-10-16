<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Skema v3 §5.3 — klasifikasi teknis layer (geometry/data kind), seed tetap
 * (lihat migration create_layer_types_table). Berbeda dari `MapType` ("Jenis
 * Peta", klasifikasi bisnis yang dipertahankan terpisah).
 */
class LayerType extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'code',
        'name',
        'data_kind',
        'geometry_type',
        'stores_features',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'stores_features' => 'boolean',
        ];
    }

    public function layers(): HasMany
    {
        return $this->hasMany(SpatialLayer::class, 'layer_type_id');
    }
}
