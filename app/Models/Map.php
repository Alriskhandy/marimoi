<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Map extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'slug',
        'title',
        'description',
        'owner_user_id',
        'owner_opd_id',
        'visibility',
        'is_active',
        'basemap_config',
        'zoom',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'basemap_config' => 'array',
            'published_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $map) {
            $map->public_id ??= (string) Str::uuid();
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function ownerOpd(): BelongsTo
    {
        return $this->belongsTo(Opd::class, 'owner_opd_id');
    }

    public function layers(): HasMany
    {
        return $this->hasMany(MapLayer::class)->orderBy('display_order');
    }

    public function layerGroups(): HasMany
    {
        return $this->hasMany(MapLayerGroup::class)->orderBy('display_order');
    }

    public function publications(): HasMany
    {
        return $this->hasMany(MapPublication::class);
    }

    public function currentPublication(): HasMany
    {
        return $this->publications()->where('is_current', true);
    }

    /**
     * Terbitkan konfigurasi map saat ini sebagai revisi publication baru — draft
     * (perubahan map/map_layers berikutnya) tidak ikut mengubah publication yang
     * sudah diterbitkan sampai publish() dipanggil lagi.
     */
    public function publish(?User $publisher = null): MapPublication
    {
        $nextRevision = ((int) $this->publications()->max('revision')) + 1;

        $snapshot = [
            'title' => $this->title,
            'description' => $this->description,
            'basemap_config' => $this->basemap_config,
            'zoom' => $this->zoom,
            'layers' => $this->layers()->get()->map(fn (MapLayer $layer) => [
                'spatial_layer_id' => $layer->spatial_layer_id,
                'display_order' => $layer->display_order,
                'display_name' => $layer->display_name,
                'is_visible' => $layer->is_visible,
                'opacity' => $layer->opacity,
                'style_config' => $layer->style_config,
                'filter_config' => $layer->filter_config,
            ])->all(),
        ];

        $this->publications()->where('is_current', true)->update([
            'is_current' => false,
            'unpublished_at' => now(),
        ]);

        $publication = $this->publications()->create([
            'revision' => $nextRevision,
            'config_snapshot' => $snapshot,
            'published_by' => $publisher?->id,
            'published_at' => now(),
            'is_current' => true,
        ]);

        $this->update(['published_at' => now()]);

        return $publication;
    }
}
