<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SharedMap extends Model
{
    protected $fillable = [
        'slug',
        'layers',
        'viewport',
        'data_type',
        'sub_type',
        'year',
        'filters',
        'expired_at',
    ];

    protected function casts(): array
    {
        return [
            'layers' => 'array',
            'viewport' => 'array',
            'filters' => 'array',
            'expired_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expired_at !== null && $this->expired_at->isPast();
    }
}
