<?php

namespace App\Services\Auth;

use App\Models\AuthenticationLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuthenticationLogger
{
    /**
     * Record an authentication event without storing raw IP or session identifiers.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function log(
        ?User $user,
        string $provider,
        string $event,
        bool $success,
        Request $request,
        ?string $failureReason = null,
        array $metadata = [],
    ): AuthenticationLog {
        return AuthenticationLog::create([
            'user_id' => $user?->id,
            'provider' => $provider,
            'event' => $event,
            'success' => $success,
            'failure_reason' => $failureReason,
            'ip_hash' => $this->hash($request->ip()),
            'user_agent' => $request->userAgent(),
            'session_id_hash' => $this->hash($request->session()->getId()),
            'request_id' => $request->header('X-Request-Id'),
            'occurred_at' => now(),
            'metadata' => $metadata ?: null,
        ]);
    }

    private function hash(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return hash('sha256', $value.config('app.key'));
    }
}
