<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Umpan balik masyarakat untuk 1 Layer ATAU 1 Data Spasial (docs/marimoi v2/
 * 04_implementation/12-implementasi-perbaikan-pemetaan.md Bagian 1.5) — beda dari
 * ProjectFeedback yang spesifik proyek strategis. CHECK constraint di database
 * memastikan tepat satu target terisi (lihat migration).
 *
 * `spatial_layer_id`/`spatial_layer_feature_id` adalah FK bigint ke tabel v2
 * `spatial_layers`/`spatial_layer_features` SECARA SPESIFIK (lihat migration
 * create_spatial_feedbacks_table) — BUKAN ke layers_v3/spatial_features_v3
 * (uuid) yang sejak Fase 3 (plan mellow-weaving-eclipse) jadi representasi
 * Eloquent `SpatialLayer`/`SpatialLayerFeature`. Karena itu relasi di sini
 * SENGAJA bukan Eloquent BelongsTo ke kelas tersebut (akan query tabel yang
 * salah) — cukup query langsung ke tabel v2 lewat DB::table(), murni untuk
 * tampilan nama Layer di admin index.
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

    public function legacyLayer(): ?object
    {
        return $this->spatial_layer_id
            ? DB::table('spatial_layers_legacy_v2')->where('id', $this->spatial_layer_id)->first()
            : null;
    }

    public function legacyFeature(): ?object
    {
        return $this->spatial_layer_feature_id
            ? DB::table('spatial_layer_features_legacy_v2 as slf')
                ->leftJoin('spatial_layers_legacy_v2 as sl', 'sl.id', '=', 'slf.spatial_layer_id')
                ->where('slf.id', $this->spatial_layer_feature_id)
                ->select('slf.id', 'sl.name as layer_name')
                ->first()
            : null;
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
