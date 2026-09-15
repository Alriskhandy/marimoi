<?php

namespace App\Models;

use Database\Factories\AuthenticationLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthenticationLog extends Model
{
    /** @use HasFactory<AuthenticationLogFactory> */
    use HasFactory;

    /**
     * The table does not have created_at/updated_at columns; occurred_at is authoritative.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'provider',
        'event',
        'success',
        'failure_reason',
        'ip_hash',
        'user_agent',
        'session_id_hash',
        'request_id',
        'occurred_at',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * Get the user associated with the log entry, if identified.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
