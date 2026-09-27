<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpatialLayerFeature extends Model
{
    protected $fillable = [
        'spatial_layer_id',
        'source_version_id',
        'external_id',
        'geometry',
        'region_id',
        'attributes',
        'created_by',
        'legacy_data_spatial_id',
    ];

    protected function casts(): array
    {
        return [
            'attributes' => 'array',
        ];
    }

    public function layer(): BelongsTo
    {
        return $this->belongsTo(SpatialLayer::class, 'spatial_layer_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(AdministrativeRegion::class, 'region_id');
    }

    public function intervensiTerkait(): HasMany
    {
        return $this->hasMany(SpatialLayerFeatureIntervention::class, 'feature_id_eksisting');
    }

    public function kondisiEksistingTerkait(): HasMany
    {
        return $this->hasMany(SpatialLayerFeatureIntervention::class, 'feature_id_intervensi');
    }
}
