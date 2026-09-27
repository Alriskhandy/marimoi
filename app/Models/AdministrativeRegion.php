<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdministrativeRegion extends Model
{
    protected $fillable = [
        'code_bps',
        'code_kemendagri',
        'name',
        'level',
        'parent_id',
        'geometry',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function features(): HasMany
    {
        return $this->hasMany(SpatialLayerFeature::class, 'region_id');
    }

    public function spatialLayers(): BelongsToMany
    {
        return $this->belongsToMany(SpatialLayer::class, 'spatial_layer_regions', 'region_id', 'spatial_layer_id');
    }

    public function scopeLevel(Builder $query, string $level): Builder
    {
        return $query->where('level', $level);
    }
}
