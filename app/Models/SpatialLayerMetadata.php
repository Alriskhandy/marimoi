<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpatialLayerMetadata extends Model
{
    protected $table = 'spatial_layer_metadata';

    protected $fillable = [
        'spatial_layer_id',
        'abstract',
        'source_name',
        'source_url',
        'license',
        'attribution',
        'contact_name',
        'contact_email',
        'contact_phone',
        'data_reference_date',
        'data_reference_year',
        'update_frequency',
        'last_verified_at',
        'lineage',
        'positional_accuracy',
        'attribute_accuracy',
        'completeness',
        'limitations',
        'language_code',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'data_reference_date' => 'date',
            'last_verified_at' => 'datetime',
        ];
    }

    public function spatialLayer(): BelongsTo
    {
        return $this->belongsTo(SpatialLayer::class);
    }
}
