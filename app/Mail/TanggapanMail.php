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
 * Email tanggapan/feedback data spasial (penerimaan & notifikasi admin) serta
 * pemberitahuan perubahan status ke warga, baik untuk feedback maupun aspirasi.
 *
 * $context menentukan sebutan di email: "Tanggapan Anda" (feedback) atau "Aspirasi Anda".
 */
class TanggapanMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public const CONTEXT_TANGGAPAN = 'tanggapan';

    public const CONTEXT_ASPIRASI = 'aspirasi';

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
        public string $type,
        public string $context = self::CONTEXT_TANGGAPAN,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        $noun = $this->noun();
        $ticket = $this->data['kode'] ?? null;

        $subject = match ($this->type) {
            'penerimaan' => "{$noun} Anda Telah Kami Terima",
            'diproses' => "{$noun} Anda Sedang Kami Proses",
            'tindaklanjut' => "{$noun} Anda Telah Kami Tindaklanjuti",
            'selesai' => "Tindak Lanjut {$noun} Anda Telah Selesai",
            'ditolak' => "Mohon Maaf, {$noun} Anda Tidak Dapat Kami Proses",
            'admin' => '[Admin Marimoi] Tanggapan Baru dari Masyarakat',
            default => 'Notifikasi dari Marimoi',
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

    private function noun(): string
    {
        return $this->context === self::CONTEXT_ASPIRASI ? 'Aspirasi' : 'Tanggapan';
    }

    /**
     * @return list<Address>
     */
    private function citizenReplyTo(): array
    {
        $email = $this->data['email'] ?? null;

        if ($this->type !== 'admin' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [];
        }

        return [new Address($email, $this->data['nama'] ?? null)];
    }

    /**
     * @return array{heading: string, paragraphs: list<string>, ticket: ?string, details: array<string, string>, quote: ?array{label: string, text: string}, steps: list<string>, action: ?array{label: string, url: string}, signature: list<string>}
     */
    private function message(): array
    {
        $data = $this->data;
        $noun = strtolower($this->noun());

        if ($this->type === 'admin') {
            return [
                'heading' => 'Halo Admin,',
                'paragraphs' => [
                    'Ada tanggapan baru dari masyarakat terhadap data spasial di Marimoi. Mohon ditindaklanjuti sesuai prosedur.',
                    'Balas email ini untuk menghubungi pengirim secara langsung.',
                ],
                'ticket' => $data['kode'] ?? null,
                'details' => $this->filled([
                    'Nama Pengirim' => $data['nama'] ?? null,
                    'Email' => $data['email'] ?? null,
                    'No. Telepon' => $data['phone'] ?? null,
                    'Proyek/Kegiatan' => $data['nama_proyek'] ?? null,
                    'Kabupaten/Kota' => $data['kabupaten_kota'] ?? null,
                    'Kecamatan' => $data['kecamatan'] ?? null,
                    'Jenis Tanggapan' => ucfirst((string) ($data['jenis_tanggapan'] ?? '')),
                    'Tanggal' => $data['tanggal'] ?? null,
                ]),
                'quote' => ['label' => 'Isi Tanggapan', 'text' => (string) ($data['tanggapan'] ?? '-')],
                'steps' => [],
                'action' => ['label' => 'Buka di Dashboard', 'url' => route('project-feedbacks.index')],
                'signature' => ['Sistem Notifikasi Marimoi'],
            ];
        }

        $intro = match ($this->type) {
            'penerimaan' => "Terima kasih telah memberikan {$noun} di Marimoi. Masukan Anda sudah kami terima dan akan segera ditinjau oleh petugas.",
            'diproses' => ucfirst($noun).' Anda saat ini sedang diproses oleh tim kami. Kami akan menghubungi Anda bila diperlukan informasi tambahan.',
            'tindaklanjut' => ucfirst($noun).' Anda telah kami tindaklanjuti.',
            'selesai' => ucfirst($noun).' Anda telah selesai kami tindaklanjuti. Terima kasih atas partisipasi Anda membantu kami meningkatkan layanan.',
            'ditolak' => "Mohon maaf, setelah kami tinjau, {$noun} Anda belum dapat kami proses karena tidak memenuhi kriteria yang ditentukan. Jika ada pertanyaan, silakan hubungi tim kami.",
            default => 'Ada pembaruan untuk '.$noun.' Anda di Marimoi.',
        };

        $response = $data['response_admin'] ?? $data['tanggapan_admin'] ?? null;
        $ticket = $data['kode'] ?? null;
        $isReceipt = $this->type === 'penerimaan';

        return [
            'heading' => 'Halo '.($data['nama'] ?? 'Bapak/Ibu').',',
            'paragraphs' => $isReceipt && $ticket
                ? [$intro, "Simpan nomor tiket di bawah ini untuk melacak perkembangan {$noun} Anda kapan saja."]
                : [$intro],
            'ticket' => $ticket,
            'details' => $isReceipt
                ? $this->filled([
                    'Proyek/Kegiatan' => $data['nama_proyek'] ?? null,
                    'Jenis Tanggapan' => ucfirst((string) ($data['jenis_tanggapan'] ?? '')),
                    'Tanggal' => $data['tanggal'] ?? null,
                ])
                : [],
            'quote' => filled($response)
                ? ['label' => 'Tanggapan Petugas', 'text' => (string) $response]
                : null,
            'steps' => $isReceipt && $ticket ? TicketLookup::trackingSteps($ticket) : [],
            'action' => ['label' => 'Lacak Status '.$this->noun(), 'url' => TicketLookup::trackingUrl($ticket)],
            'signature' => ['Salam hangat,', 'Tim Marimoi'],
        ];
    }

    /**
     * @param  array<string, mixed>  $details
     * @return array<string, string>
     */
    private function filled(array $details): array
    {
        return array_map('strval', array_filter($details, fn ($value) => filled($value)));
    }
}
