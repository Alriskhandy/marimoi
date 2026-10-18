<?php

namespace App\Http\Controllers;

use App\Models\MailLog;
use Illuminate\Http\Request;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Sistem › Log Email (khusus super-admin): riwayat email terkirim/gagal dan kondisi antrian email.
 */
class MailLogController extends Controller
{
    /**
     * Mailer yang tidak benar-benar mengirim email ke penerima.
     */
    private const NON_DELIVERING_MAILERS = ['log', 'array'];

    public function index(Request $request): View
    {
        $status = array_key_exists($request->query('status'), MailLog::STATUSES) ? $request->query('status') : null;
        $search = trim((string) $request->query('q'));

        $logs = MailLog::query()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $like = '%'.mb_strtolower($search).'%';
                $query->whereRaw('LOWER(recipients) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(subject) LIKE ?', [$like]);
            }))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $counts = MailLog::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $mailer = (string) config('mail.default');

        return view('backend.pages.mail-logs.index', [
            'logs' => $logs,
            'status' => $status,
            'search' => $search,
            'stats' => [
                'sent' => (int) ($counts[MailLog::STATUS_SENT] ?? 0),
                'failed' => (int) ($counts[MailLog::STATUS_FAILED] ?? 0),
                'queued' => $this->queuedMailCount(),
                'sent_today' => MailLog::where('status', MailLog::STATUS_SENT)->where('created_at', '>=', today())->count(),
            ],
            'mailer' => $mailer,
            'mailerDelivers' => ! in_array($mailer, self::NON_DELIVERING_MAILERS, true),
            'queueConnection' => (string) config('queue.default'),
            'retentionDays' => MailLog::RETENTION_DAYS,
        ]);
    }

    /**
     * Email yang masih menunggu worker antrian (hanya bisa dihitung untuk driver database).
     */
    private function queuedMailCount(): ?int
    {
        if (config('queue.default') !== 'database') {
            return null;
        }

        $table = config('queue.connections.database.table', 'jobs');

        if (! Schema::hasTable($table)) {
            return null;
        }

        return DB::table($table)
            ->where('payload', 'like', '%'.class_basename(SendQueuedMailable::class).'%')
            ->count();
    }
}
