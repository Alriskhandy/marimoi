<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Satu pintu pengiriman email untuk Aspirasi masyarakat & Feedback data spasial.
 *
 * - Penerima admin diambil dari MAIL_ADMIN_ADDRESS (boleh beberapa, dipisah koma);
 *   bila kosong, jatuh ke email user ber-role super-admin yang aktif.
 * - Kegagalan mengantrikan email TIDAK boleh menggagalkan request: data warga sudah
 *   tersimpan, jadi error cukup dicatat di log.
 */
class AspirasiMailer
{
    /**
     * Alamat email admin penerima notifikasi aspirasi/feedback baru.
     *
     * @return list<string>
     */
    public static function adminRecipients(): array
    {
        $configured = self::validAddresses((array) config('mail.admin_addresses', []));

        if ($configured !== []) {
            return $configured;
        }

        return self::validAddresses(
            User::query()
                ->whereHas('role', fn ($query) => $query->where('slug', 'super-admin'))
                ->where('is_active', true)
                ->pluck('email')
                ->all()
        );
    }

    /**
     * Antrikan email ke tiap penerima secara terpisah (alamat warga/OPD tidak saling terlihat).
     *
     * @param  string|array<int, string|null>|null  $recipients
     */
    public static function queue(string|array|null $recipients, Mailable $mailable): void
    {
        foreach (self::validAddresses((array) $recipients) as $recipient) {
            try {
                Mail::to($recipient)->queue(clone $mailable);
            } catch (Throwable $exception) {
                Log::error('Gagal mengantrikan email '.class_basename($mailable), [
                    'recipient' => $recipient,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    /**
     * @param  array<int, mixed>  $addresses
     * @return list<string>
     */
    private static function validAddresses(array $addresses): array
    {
        return collect($addresses)
            ->map(fn ($address) => is_string($address) ? strtolower(trim($address)) : null)
            ->filter(fn (?string $address) => $address && filter_var($address, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }
}
