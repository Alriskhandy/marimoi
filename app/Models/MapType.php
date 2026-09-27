<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MapType extends Model
{
    protected $fillable = [
        'slug',
        'nama',
        'deskripsi',
        'icon',
        'urutan',
        'is_active',
        'konfigurasi',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'konfigurasi' => 'array',
        ];
    }

    public function spatialLayers(): HasMany
    {
        return $this->hasMany(SpatialLayer::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('urutan');
    }
}
