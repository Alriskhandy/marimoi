<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MapShare extends Model
{
    protected $fillable = [
        'map_publication_id',
        'token_hash',
        'created_by',
        'expires_at',
        'revoked_at',
        'is_active',
        'access_count',
        'last_accessed_at',
        'qr_path',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'is_active' => 'boolean',
            'last_accessed_at' => 'datetime',
        ];
    }

    public function publication(): BelongsTo
    {
        return $this->belongsTo(MapPublication::class, 'map_publication_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function accesses(): HasMany
    {
        return $this->hasMany(MapShareAccess::class);
    }

    /**
     * Buat share baru untuk sebuah publication. Token acak panjang (bukan slug/ID
     * berurutan) hanya dikembalikan sekali di sini sebagai plaintext — yang disimpan
     * ke database hanya hash-nya (token_hash), sesuai db-schema-v2.md §"URL share".
     *
     * @return array{share: self, token: string}
     */
    public static function generateFor(MapPublication $publication, ?User $creator = null, ?\DateTimeInterface $expiresAt = null): array
    {
        $token = Str::random(48);

        $share = self::create([
            'map_publication_id' => $publication->id,
            'token_hash' => hash('sha256', $token),
            'created_by' => $creator?->id,
            'expires_at' => $expiresAt,
            // Eksplisit diisi (bukan mengandalkan default kolom di database) — Eloquent
            // tidak membaca ulang default DB ke instance PHP setelah create(), jadi
            // is_active akan jadi null di memori (falsy) meski true di database bila
            // tidak disertakan di sini.
            'is_active' => true,
            'access_count' => 0,
        ]);

        return ['share' => $share, 'token' => $token];
    }

    public static function findByToken(string $token): ?self
    {
        return self::where('token_hash', hash('sha256', $token))->first();
    }

    /**
     * Share valid untuk diakses bila aktif, belum dicabut, dan belum kedaluwarsa.
     * expires_at null berarti tidak ada batas waktu.
     */
    public function isValid(): bool
    {
        if (! $this->is_active || $this->revoked_at !== null) {
            return false;
        }

        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    public function revoke(): void
    {
        $this->update(['revoked_at' => now(), 'is_active' => false]);
    }

    public function recordAccess(?string $ipHash = null, ?string $userAgent = null, ?string $referer = null, int $responseStatus = 200): MapShareAccess
    {
        $this->increment('access_count');
        $this->update(['last_accessed_at' => now()]);

        return $this->accesses()->create([
            'accessed_at' => now(),
            'ip_hash' => $ipHash,
            'user_agent' => $userAgent,
            'referer' => $referer,
            'response_status' => $responseStatus,
        ]);
    }
}
