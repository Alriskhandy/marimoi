<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MapType extends Model
{
    protected $fillable = [
        'slug',
        'nama',
        'deskripsi',
        'sumber_data',
        'opd_penanggung_jawab_id',
        'tanggal_data',
        'urutan',
        'is_active',
        'konfigurasi',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'konfigurasi' => 'array',
            'tanggal_data' => 'date',
        ];
    }

    public function spatialLayers(): HasMany
    {
        return $this->hasMany(SpatialLayer::class);
    }

    public function dynamicAttributes(): HasMany
    {
        return $this->hasMany(MapTypeDynamicAttribute::class);
    }

    public function opdPenanggungJawab(): BelongsTo
    {
        return $this->belongsTo(Opd::class, 'opd_penanggung_jawab_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('urutan');
    }
}
