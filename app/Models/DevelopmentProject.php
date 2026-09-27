<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DevelopmentProject extends Model
{
    protected $fillable = [
        'project_code',
        'name',
        'description',
        'owner_opd_id',
        'sector_id',
        'fiscal_year',
        'status',
        'budget_amount',
        'funding_source',
        'is_active',
        'needs_review',
        'created_by',
        'legacy_data_spatial_id',
    ];

    protected function casts(): array
    {
        return [
            'budget_amount' => 'decimal:2',
            'is_active' => 'boolean',
            'needs_review' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $project) {
            $project->public_id ??= (string) Str::uuid();
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Opd::class, 'owner_opd_id');
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(ProjectLocation::class);
    }

    public function regions(): BelongsToMany
    {
        return $this->belongsToMany(AdministrativeRegion::class, 'project_regions', 'development_project_id', 'region_id');
    }

    public function progressReports(): HasMany
    {
        return $this->hasMany(ProjectProgressReport::class);
    }

    public function scopeNeedsReview(Builder $query): Builder
    {
        return $query->where('needs_review', true);
    }
}
