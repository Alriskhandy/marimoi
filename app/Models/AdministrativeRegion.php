<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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

    /**
     * Daftar wilayah aktif dikelompokkan per level (provinsi/kabupaten_kota/
     * kecamatan) untuk dropdown `region_id` di form Data Spasial (§5.7 butir
     * 4) — hanya ~130 baris total, jadi select datar per level sudah cukup,
     * tidak perlu cascading select bertingkat.
     *
     * @return Collection<string, \Illuminate\Database\Eloquent\Collection>
     */
    public static function optionsGroupedByLevel(): Collection
    {
        return self::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'level'])
            ->groupBy('level');
    }
}
