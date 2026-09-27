<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpatialLayerFeatureIntervention extends Model
{
    protected $fillable = [
        'feature_id_eksisting',
        'feature_id_intervensi',
        'jenis_hubungan',
        'created_by',
    ];

    public function eksisting(): BelongsTo
    {
        return $this->belongsTo(SpatialLayerFeature::class, 'feature_id_eksisting');
    }

    public function intervensi(): BelongsTo
    {
        return $this->belongsTo(SpatialLayerFeature::class, 'feature_id_intervensi');
    }
}
