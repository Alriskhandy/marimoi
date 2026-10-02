<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Skema v3 §5.7 — riwayat impor file ke spatial_features. Tanpa
 * layer_versions (dokumen §1.5), tabel ini satu-satunya riwayat perubahan
 * data layer.
 */
class LayerImport extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'layer_id',
        'layer_source_id',
        'original_filename',
        'storage_path',
        'file_format',
        'file_size_bytes',
        'checksum_sha256',
        'source_srid',
        'target_srid',
        'encoding',
        'import_mode',
        'status',
        'detected_fields',
        'total_features',
        'imported_features',
        'failed_features',
        'error_message',
        'log',
        'started_at',
        'finished_at',
        'imported_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'detected_fields' => 'array',
            'log' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $import) {
            $import->id ??= (string) Str::uuid();
            $import->created_at ??= now();
        });
    }

    public function layer(): BelongsTo
    {
        return $this->belongsTo(SpatialLayer::class, 'layer_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(LayerSource::class, 'layer_source_id');
    }

    public function attributeMappings(): HasMany
    {
        return $this->hasMany(LayerAttributeMapping::class, 'layer_import_id');
    }

    public function features(): HasMany
    {
        return $this->hasMany(SpatialLayerFeature::class, 'layer_import_id');
    }
}
