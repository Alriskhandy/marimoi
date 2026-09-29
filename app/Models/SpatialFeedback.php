<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Umpan balik masyarakat untuk 1 Layer ATAU 1 Data Spasial (docs/marimoi v2/
 * 04_implementation/12-implementasi-perbaikan-pemetaan.md Bagian 1.5) — beda dari
 * ProjectFeedback yang spesifik proyek strategis. CHECK constraint di database
 * memastikan tepat satu target terisi (lihat migration).
 */
class SpatialFeedback extends Model
{
    // "feedback" uncountable di Inflector Laravel -> tebakan nama tabel otomatis
    // jadi "spatial_feedback" (singular), bukan "spatial_feedbacks" — set eksplisit.
    protected $table = 'spatial_feedbacks';

    protected $fillable = [
        'spatial_layer_id',
        'spatial_layer_feature_id',
        'nama_pemberi',
        'email',
        'phone',
        'pesan',
        'status',
        'response_admin',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
        ];
    }

    public function layer(): BelongsTo
    {
        return $this->belongsTo(SpatialLayer::class, 'spatial_layer_id');
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(SpatialLayerFeature::class, 'spatial_layer_feature_id');
    }

    public function scopeUntukLayer(Builder $query): Builder
    {
        return $query->whereNotNull('spatial_layer_id');
    }

    public function scopeUntukDataSpasial(Builder $query): Builder
    {
        return $query->whereNotNull('spatial_layer_feature_id');
    }
}
