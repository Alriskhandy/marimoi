<?php

namespace App\Support;

use App\Models\Aspirasi;
use App\Models\ProjectFeedback;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Pelacakan tiket publik di halaman Aspirasi (#lacak) untuk dua jenis kiriman warga:
 * Aspirasi (MARIMOI-ASP-...) dan Feedback data spasial (MARIMOI-FDB-..., lihat ProjectFeedback::ticketCode()).
 *
 * Tiket hanya terbuka bila email ATAU nomor WhatsApp cocok dengan data pengirim.
 */
class TicketLookup
{
    public const TYPE_ASPIRASI = 'aspirasi';

    public const TYPE_FEEDBACK = 'feedback';

    /**
     * Status feedback dipetakan ke tahapan yang sama dengan aspirasi.
     */
    private const FEEDBACK_STATUS = [
        'pending' => 'pending',
        'ditinjau' => 'diproses',
        'ditindaklanjuti' => 'diproses',
        'selesai' => 'selesai',
    ];

    /**
     * Link halaman lacak dengan nomor tiket terisi otomatis.
     */
    public static function trackingUrl(?string $ticket = null): string
    {
        return route('tampil.aspirasi', array_filter(['tiket' => $ticket])).'#lacak';
    }

    /**
     * Panduan singkat pelacakan untuk email konfirmasi ke warga.
     *
     * @return list<string>
     */
    public static function trackingSteps(string $ticket): array
    {
        return [
            'Buka halaman Lacak Status melalui tombol di bawah, atau kunjungi '.route('tampil.aspirasi').' lalu pilih tab "Lacak Status".',
            'Masukkan nomor tiket '.$ticket.'.',
            'Masukkan email atau nomor WhatsApp yang Anda gunakan saat mengirim.',
            'Klik "Cek Status" untuk melihat status terkini dan tanggapan petugas.',
        ];
    }

    /**
     * @return array{type: string, id: int}|null
     */
    public static function find(string $ticket, string $contact): ?array
    {
        $ticket = Str::upper(trim($ticket));

        if (preg_match('/^MARIMOI-FDB-\d{8}-(\d+)$/', $ticket, $matches)) {
            $feedback = ProjectFeedback::find((int) $matches[1]);

            return $feedback
                && $feedback->ticketCode() === $ticket
                && self::contactMatches($feedback->email, $feedback->phone, $contact)
                    ? ['type' => self::TYPE_FEEDBACK, 'id' => $feedback->id]
                    : null;
        }

        $aspirasi = Aspirasi::where('nomor_tiket', $ticket)->first();

        return $aspirasi && self::contactMatches($aspirasi->email, $aspirasi->phone, $contact)
            ? ['type' => self::TYPE_ASPIRASI, 'id' => $aspirasi->id]
            : null;
    }

    /**
     * Ringkasan seragam untuk panel hasil lacak.
     *
     * @param  array{type: string, id: int}|null  $reference
     * @return array{jenis: string, judul: string, nomor_tiket: string, status: string, diajukan: Carbon, tanggal_respon: ?Carbon, tanggapan: ?string}|null
     */
    public static function summary(?array $reference): ?array
    {
        if (! $reference) {
            return null;
        }

        if ($reference['type'] === self::TYPE_FEEDBACK) {
            $feedback = ProjectFeedback::find($reference['id']);

            return $feedback ? [
                'jenis' => 'Tanggapan',
                'judul' => $feedback->nama_proyek ?: 'Tanggapan data spasial',
                'nomor_tiket' => $feedback->ticketCode(),
                'status' => self::FEEDBACK_STATUS[$feedback->status] ?? 'pending',
                'diajukan' => $feedback->created_at,
                'tanggal_respon' => $feedback->responded_at,
                'tanggapan' => $feedback->response_admin,
            ] : null;
        }

        $aspirasi = Aspirasi::find($reference['id']);

        return $aspirasi ? [
            'jenis' => 'Aspirasi',
            'judul' => $aspirasi->judul_aspirasi,
            'nomor_tiket' => $aspirasi->nomor_tiket,
            'status' => $aspirasi->status,
            'diajukan' => $aspirasi->created_at,
            'tanggal_respon' => $aspirasi->tanggal_respon,
            'tanggapan' => $aspirasi->tanggapan_admin,
        ] : null;
    }

    private static function contactMatches(?string $email, ?string $phone, string $contact): bool
    {
        $contact = trim($contact);

        return ($email && Str::lower($email) === Str::lower($contact))
            || ($phone && self::normalizePhone($phone) !== '' && self::normalizePhone($phone) === self::normalizePhone($contact));
    }

    /**
     * Buang karakter non-digit, lalu samakan prefix 0/62 ("0812...", "62812...", "+62812..." dianggap sama).
     */
    private static function normalizePhone(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        return preg_replace('/^(0|62)/', '', $digits) ?? $digits;
    }
}
