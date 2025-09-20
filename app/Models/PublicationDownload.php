<?php
// app/Models/PublicationDownload.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PublicationDownload extends Model
{
    use HasFactory;

    protected $fillable = [
        'publication_id',
        'name',
        'email',
        'phone',
        'organization',
        'position',
        'purpose',
        'ip_address',
        'user_agent',
        'downloaded_at',
    ];

    protected $casts = [
        'downloaded_at' => 'datetime',
    ];

    public function publication(): BelongsTo
    {
        return $this->belongsTo(Publication::class);
    }

    public function survey(): HasOne
    {
        return $this->hasOne(Survey::class);
    }

    // Scope untuk filter berdasarkan tanggal
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('downloaded_at', [$startDate, $endDate]);
    }

    // Scope untuk filter berdasarkan publikasi
    public function scopeByPublication($query, $publicationId)
    {
        return $query->where('publication_id', $publicationId);
    }
}