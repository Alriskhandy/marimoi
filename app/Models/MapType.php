<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Relasi `spatialLayers()` (hasMany ke `layers` lewat `map_type_id`) dihapus
 * 2026-10-06 bersama kolom `layers.map_type_id` itu sendiri — lihat migration
 * drop_map_type_id_and_visibility_from_layers_table. Layer tidak lagi bisa
 * dikaitkan ke Jenis Peta.
 */
class MapType extends Model
{
    protected $fillable = [
        'slug',
        'nama',
        'deskripsi',
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

    public function dynamicAttributes(): HasMany
    {
        return $this->hasMany(MapTypeDynamicAttribute::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('urutan');
    }
}
