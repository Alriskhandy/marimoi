<?php

namespace App\Listeners;

use App\Models\MailLog;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Address;
use Throwable;

/**
 * Catat setiap email ke tabel mail_logs (menu Sistem › Log Email):
 * - MessageSent → terkirim (diserahkan ke mailer)
 * - JobFailed untuk SendQueuedMailable → gagal permanen setelah semua percobaan ulang habis
 *
 * Pencatatan tidak boleh mengganggu pengiriman email: kegagalan menulis log hanya di-log.
 */
class RecordMailLog
{
    public function handleMessageSent(MessageSent $event): void
    {
        $message = $event->sent->getOriginalMessage();

        $this->record([
            'status' => MailLog::STATUS_SENT,
            'mailable' => $event->data['__laravel_mailable'] ?? $event->data['__laravel_notification'] ?? null,
            'subject' => method_exists($message, 'getSubject') ? $message->getSubject() : null,
            'recipients' => $this->addresses($event->sent->getEnvelope()->getRecipients()),
            'message_id' => $event->sent->getMessageId(),
        ]);
    }

    public function handleJobFailed(JobFailed $event): void
    {
        $command = $this->queuedMailable($event->job->payload());

        if (! $command) {
            return;
        }

        $mailable = $command->mailable;

        $this->record([
            'status' => MailLog::STATUS_FAILED,
            'mailable' => get_class($mailable),
            'subject' => $this->subjectOf($mailable),
            'recipients' => collect($mailable->to)->pluck('address')->filter()->implode(', '),
            'error' => Str::limit($event->exception->getMessage(), 2000),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function queuedMailable(array $payload): ?SendQueuedMailable
    {
        if (($payload['data']['commandName'] ?? null) !== SendQueuedMailable::class) {
            return null;
        }

        try {
            $serialized = $payload['data']['command'];

            if (! str_starts_with($serialized, 'O:')) {
                $serialized = app(Encrypter::class)->decrypt($serialized);
            }

            $command = unserialize($serialized);

            return $command instanceof SendQueuedMailable ? $command : null;
        } catch (Throwable $exception) {
            Log::warning('Gagal membaca email yang gagal dari payload antrian', ['error' => $exception->getMessage()]);

            return null;
        }
    }

    private function subjectOf(object $mailable): ?string
    {
        if (method_exists($mailable, 'envelope')) {
            try {
                return $mailable->envelope()->subject;
            } catch (Throwable) {
                // Jatuh ke properti subject di bawah.
            }
        }

        return $mailable->subject ?? null;
    }

    /**
     * @param  array<int, Address>  $addresses
     */
    private function addresses(array $addresses): string
    {
        return collect($addresses)->map(fn (Address $address) => $address->getAddress())->implode(', ');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function record(array $attributes): void
    {
        try {
            MailLog::create($attributes + ['mailer' => config('mail.default')]);
        } catch (Throwable $exception) {
            Log::error('Gagal mencatat log email', ['error' => $exception->getMessage()]);
        }
    }
}
