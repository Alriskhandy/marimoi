<?php
// app/Models/Survey.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Survey extends Model
{
    use HasFactory;

    protected $fillable = [
        'publication_id',
        'publication_download_id',
        'name',
        'email',
        'phone',
        'organization',
        'position',
        'survey_type',
        'rating',
        'feedback',
        'suggestions',
        'additional_data',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'additional_data' => 'array',
        'rating' => 'integer',
    ];

    public function publication(): BelongsTo
    {
        return $this->belongsTo(Publication::class);
    }

    public function publicationDownload(): BelongsTo
    {
        return $this->belongsTo(PublicationDownload::class);
    }

    // Scope untuk survey umum (tanpa download)
    public function scopeGeneral($query)
    {
        return $query->where('survey_type', 'general');
    }

    // Scope untuk survey download
    public function scopeDownload($query)
    {
        return $query->where('survey_type', 'download');
    }

    // Method untuk mendapatkan survey terbaru per email (untuk menghindari spam)
    public static function getLatestByEmail($email)
    {
        return self::where('email', $email)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    // Method untuk cek apakah email sudah survey dalam 24 jam terakhir
    public static function hasRecentSurvey($email, $hours = 24)
    {
        return self::where('email', $email)
            ->where('created_at', '>=', now()->subHours($hours))
            ->exists();
    }

    // Scope untuk filter berdasarkan rating
    public function scopeByRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    // Scope untuk filter berdasarkan tanggal
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}