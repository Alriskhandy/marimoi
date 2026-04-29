<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Layer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'style',
        'parent_id',
        'is_active',
        'user_id',
    ];

    protected $casts = [
        'style' => 'array',
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Layer::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Layer::class, 'parent_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function features(): HasMany
    {
        return $this->hasMany(Feature::class);
    }
}