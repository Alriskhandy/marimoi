<?php

namespace App\Mail;

use App\Support\TicketLookup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email aspirasi baru: konfirmasi ke warga (penerimaan), notifikasi admin (admin),
 * dan notifikasi OPD tujuan usulan (opd).
 */
class AspirasiMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public const TYPE_PENERIMAAN = 'penerimaan';

    public const TYPE_ADMIN = 'admin';

    public const TYPE_OPD = 'opd';

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(public array $data, public string $type)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        $ticket = $this->data['nomor_tiket'] ?? null;

        $subject = match ($this->type) {
            self::TYPE_PENERIMAAN => 'Aspirasi Anda Telah Kami Terima',
            self::TYPE_ADMIN => '[Admin Marimoi] Aspirasi Baru Diterima',
            self::TYPE_OPD => '[OPD Marimoi] Aspirasi Baru untuk Ditindaklanjuti',
            default => 'Notifikasi Aspirasi - Marimoi',
        };

        return new Envelope(
            subject: $ticket ? $subject.' ('.$ticket.')' : $subject,
            replyTo: $this->citizenReplyTo(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notification',
            text: 'emails.notification-text',
            with: $this->message(),
        );
    }

    /**
     * Admin & OPD bisa langsung membalas ke warga dari kotak masuk mereka.
     *
     * @return list<Address>
     */
    private function citizenReplyTo(): array
    {
        $email = $this->data['email'] ?? null;

        if ($this->type === self::TYPE_PENERIMAAN || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [];
        }

        return [new Address($email, $this->data['nama_pengirim'] ?? null)];
    }

    /**
     * @return array{heading: string, paragraphs: list<string>, ticket: ?string, details: array<string, string>, quote: ?array{label: string, text: string}, steps: list<string>, action: ?array{label: string, url: string}, signature: list<string>}
     */
    private function message(): array
    {
        $data = $this->data;
        $isi = ['label' => 'Isi Aspirasi', 'text' => (string) ($data['isi_aspirasi'] ?? '-')];

        if ($this->type === self::TYPE_PENERIMAAN) {
            $ticket = $data['nomor_tiket'] ?? null;

            return [
                'heading' => 'Halo '.($data['nama_pengirim'] ?? 'Bapak/Ibu').',',
                'paragraphs' => [
                    'Terima kasih telah mengirimkan aspirasi melalui Marimoi. Aspirasi Anda telah kami terima dan akan segera ditinjau oleh petugas.',
                    'Simpan nomor tiket di bawah ini untuk melacak perkembangan aspirasi Anda kapan saja.',
                ],
                'ticket' => $ticket,
                'details' => $this->filled([
                    'Jenis' => ucwords((string) ($data['jenis_aspirasi'] ?? '')),
                    'Judul' => $data['judul_aspirasi'] ?? null,
                    'Tanggal' => $data['tanggal'] ?? null,
                ]),
                'quote' => null,
                'steps' => $ticket ? TicketLookup::trackingSteps($ticket) : [],
                'action' => ['label' => 'Lacak Status Aspirasi', 'url' => TicketLookup::trackingUrl($ticket)],
                'signature' => ['Salam hangat,', 'Tim Marimoi'],
            ];
        }

        $isOpd = $this->type === self::TYPE_OPD;

        return [
            'heading' => $isOpd ? 'Halo Tim OPD,' : 'Halo Admin,',
            'paragraphs' => [
                $isOpd
                    ? 'Ada aspirasi baru yang perlu ditindaklanjuti oleh OPD Anda melalui Marimoi.'
                    : 'Ada aspirasi baru yang masuk melalui Marimoi. Mohon ditindaklanjuti sesuai prosedur.',
                'Balas email ini untuk menghubungi pengirim secara langsung.',
            ],
            'ticket' => $data['nomor_tiket'] ?? null,
            'details' => $this->filled([
                'Nama Pengirim' => $data['nama_pengirim'] ?? null,
                'Email' => $data['email'] ?? null,
                'No. WhatsApp' => $data['phone'] ?? null,
                'Alamat' => $data['alamat'] ?? null,
                'Jenis' => ucwords((string) ($data['jenis_aspirasi'] ?? '')),
                'Kategori' => $data['kategori_aspirasi'] ?? null,
                'OPD Terkait' => $data['opd_terkait'] ?? null,
                'Judul' => $data['judul_aspirasi'] ?? null,
                'Tanggal' => $data['tanggal'] ?? null,
            ]),
            'quote' => $isi,
            'steps' => [],
            'action' => isset($data['id_aspirasi'])
                ? ['label' => 'Buka di Dashboard', 'url' => route('aspirasi.show', $data['id_aspirasi'])]
                : null,
            'signature' => ['Sistem Notifikasi Marimoi'],
        ];
    }

    /**
     * Buang baris detail yang kosong atau bernilai "N/A".
     *
     * @param  array<string, mixed>  $details
     * @return array<string, string>
     */
    private function filled(array $details): array
    {
        return array_map('strval', array_filter(
            $details,
            fn ($value) => filled($value) && $value !== 'N/A'
        ));
    }
}
