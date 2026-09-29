<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SpatialLayer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'slug',
        'name',
        'title',
        'description',
        'layer_class',
        'source_type',
        'legacy_category_id',
        'map_type_id',
        'sector_id',
        'owner_user_id',
        'owner_opd_id',
        'geometry_type',
        'color',
        'is_marker',
        'srid',
        'min_zoom',
        'max_zoom',
        'visibility',
        'is_active',
        'is_downloadable',
        'parent_id',
        'is_group',
        'atribut_schema',
        'published_at',
        'thumbnail_path',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_downloadable' => 'boolean',
            'is_marker' => 'boolean',
            'is_group' => 'boolean',
            'atribut_schema' => 'array',
            'published_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $layer) {
            $layer->public_id ??= (string) Str::uuid();
        });
    }

    public function mapType(): BelongsTo
    {
        return $this->belongsTo(MapType::class);
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function metadata(): HasOne
    {
        return $this->hasOne(SpatialLayerMetadata::class);
    }

    public function features(): HasMany
    {
        return $this->hasMany(SpatialLayerFeature::class);
    }

    public function regions(): BelongsToMany
    {
        return $this->belongsToMany(AdministrativeRegion::class, 'spatial_layer_regions', 'spatial_layer_id', 'region_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function scopeSelectable(Builder $query): Builder
    {
        return $query->where('is_group', false);
    }

    public function scopeGroups(Builder $query): Builder
    {
        return $query->where('is_group', true);
    }

    /**
     * Bangun rule Laravel Validator dari atribut_schema layer ini, dipakai untuk
     * memvalidasi payload attributes yang dikirim form data spasial.
     *
     * @return array<string, string>
     */
    public function atributValidationRules(): array
    {
        $rules = [];

        foreach ($this->atribut_schema['fields'] ?? [] as $field) {
            $parts = [($field['required'] ?? false) ? 'required' : 'nullable'];
            $parts[] = match ($field['type'] ?? 'string') {
                'number' => 'numeric',
                'date' => 'date',
                'select' => 'in:'.implode(',', $field['options'] ?? []),
                default => 'string',
            };
            $rules['attributes.'.$field['key']] = implode('|', $parts);
        }

        return $rules;
    }
}
