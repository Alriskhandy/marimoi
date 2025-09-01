<?php
// app/Models/Visitor.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Visitor extends Model
{
    protected $fillable = [
        'ip',
        'user_agent',
        'latitude', 
        'longitude',
        'country',
        'city',
        'page_visited'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    // Scope untuk visitor hari ini
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    // Scope untuk visitor unique (berdasarkan IP)
    public function scopeUnique($query) 
    {
        return $query->distinct('ip');
    }

    // Accessor untuk lokasi
    public function getLocationAttribute()
    {
        if ($this->city && $this->country) {
            return $this->city . ', ' . $this->country;
        }
        return $this->country ?? 'Unknown';
    }
}