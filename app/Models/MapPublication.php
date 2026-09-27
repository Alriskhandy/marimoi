<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MapPublication extends Model
{
    protected $fillable = [
        'map_id',
        'revision',
        'config_snapshot',
        'layer_version_snapshot',
        'published_by',
        'published_at',
        'unpublished_at',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'config_snapshot' => 'array',
            'layer_version_snapshot' => 'array',
            'published_at' => 'datetime',
            'unpublished_at' => 'datetime',
            'is_current' => 'boolean',
        ];
    }

    public function map(): BelongsTo
    {
        return $this->belongsTo(Map::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(MapShare::class);
    }
}
