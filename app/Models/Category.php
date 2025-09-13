<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Model untuk Categories
class Category extends Model
{
    protected $table = 'categories';
    protected $fillable = [
        'type',
        'nama',
        'warna',
        'icon',
        'is_marker',
        'user_id',
        'deskripsi',
        'parent_id',
        'is_active',
        'gambar',
    ];

    protected $casts = [
        'is_marker' => 'boolean',
    ];

    // Relasi hierarki
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // Relasi ke data spatial
    public function dataSpatial(): HasMany
    {
        return $this->hasMany(DataSpatial::class, 'kategori_id');
    }

    // Scopes berdasarkan type
    public function scopeLayers($query)
    {
        return $query->where('type', 'tematik');
    }

    public function scopeMusenbangs($query)
    {
        return $query->where('type', 'usulan_musrenbang');
    }

    public function scopePokirDprds($query)
    {
        return $query->where('type', 'pokir_dprd');
    }

    public function scopePsd($query)
    {
        return $query->where('type', 'psd');
    }

    public function scopePsn($query)
    {
        return $query->where('type', 'psn');
    }

    public function scopeMarkers($query)
    {
        return $query->where('is_marker', true);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }
}
