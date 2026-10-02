<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Skema v3 §5.10 — simbolisasi layer (warna/ikon/opacity/dll, sebelumnya
 * nempel langsung di kolom spatial_layers v2).
 */
class LayerStyle extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'layer_id',
        'name',
        'style_type',
        'renderer',
        'classification_field',
        'definition',
        'legend',
        'sld',
        'is_default',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'definition' => 'array',
            'legend' => 'array',
            'is_default' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $style) {
            $style->id ??= (string) Str::uuid();
        });
    }

    public function layer(): BelongsTo
    {
        return $this->belongsTo(SpatialLayer::class, 'layer_id');
    }
}
