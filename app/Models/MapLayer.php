<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MapLayer extends Model
{
    protected $fillable = [
        'map_id',
        'spatial_layer_id',
        'layer_group_id',
        'display_order',
        'display_name',
        'is_visible',
        'opacity',
        'style_config',
        'filter_config',
        'chart_config',
        'min_zoom',
        'max_zoom',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'opacity' => 'float',
            'style_config' => 'array',
            'filter_config' => 'array',
            'chart_config' => 'array',
        ];
    }

    public function map(): BelongsTo
    {
        return $this->belongsTo(Map::class);
    }

    public function spatialLayer(): BelongsTo
    {
        return $this->belongsTo(SpatialLayer::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(MapLayerGroup::class, 'layer_group_id');
    }
}
