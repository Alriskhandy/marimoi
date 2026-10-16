<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Skema v3 §5.9 — pemetaan kolom file sumber ke atribut standar, per impor.
 * `attribute_definition_id` mengarah ke `MetadataDefinition` (katalog global
 * yang sudah ada), BUKAN ke tabel `layer_attribute_definitions` yang sengaja
 * tidak dibuat — sistem atribut dinamis tetap di-scope per Jenis Peta.
 */
class LayerAttributeMapping extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'layer_import_id',
        'source_field_name',
        'source_field_type',
        'attribute_definition_id',
        'is_ignored',
        'transform',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_ignored' => 'boolean',
            'transform' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $mapping) {
            $mapping->id ??= (string) Str::uuid();
            $mapping->created_at ??= now();
        });
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(LayerImport::class, 'layer_import_id');
    }

    public function attributeDefinition(): BelongsTo
    {
        return $this->belongsTo(MetadataDefinition::class, 'attribute_definition_id');
    }
}
