<?php

namespace App\Models;

use App\Mail\AspirasiMail;
use App\Mail\TanggapanMail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/**
 * Riwayat pengiriman email (dicatat oleh App\Listeners\RecordMailLog). Isi email tidak disimpan.
 */
class MailLog extends Model
{
    use HasFactory, MassPrunable;

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUSES = [
        self::STATUS_SENT => 'Terkirim',
        self::STATUS_FAILED => 'Gagal',
    ];

    /**
     * Log lebih tua dari ini dibersihkan oleh `php artisan model:prune` (terjadwal harian).
     */
    public const RETENTION_DAYS = 90;

    /**
     * Nama tampilan jenis email.
     */
    private const MAILABLE_LABELS = [
        AspirasiMail::class => 'Aspirasi',
        TanggapanMail::class => 'Tanggapan/Status',
    ];

    protected $fillable = [
        'status',
        'mailable',
        'subject',
        'recipients',
        'mailer',
        'message_id',
        'error',
    ];

    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(self::RETENTION_DAYS));
    }

    public function mailableLabel(): string
    {
        return self::MAILABLE_LABELS[$this->mailable] ?? ($this->mailable ? class_basename($this->mailable) : '-');
    }
}
