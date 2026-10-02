<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Skema v3 §5.6 — asal data layer (file unggahan atau layanan eksternal).
 */
class LayerSource extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'layer_id',
        'source_type',
        'name',
        'url',
        'service_layer_name',
        'format',
        'crs',
        'auth_type',
        'credential_ref',
        'options',
        'is_primary',
        'health_status',
        'last_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_primary' => 'boolean',
            'last_checked_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $source) {
            $source->id ??= (string) Str::uuid();
        });
    }

    public function layer(): BelongsTo
    {
        return $this->belongsTo(SpatialLayer::class, 'layer_id');
    }

    public function imports(): HasMany
    {
        return $this->hasMany(LayerImport::class, 'layer_source_id');
    }
}
