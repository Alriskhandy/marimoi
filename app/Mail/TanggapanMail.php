<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TanggapanMail extends Mailable
{
     use Queueable, SerializesModels;

    public $data;
    public $type;

    /**
     * Create a new message instance.
     */
    public function __construct($data, $type)
    {
        $this->data = $data;
        $this->type = $type;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = '';
       switch ($this->type) {
            case 'penerimaan':
                $subject = 'Terima kasih atas tanggapan Anda di Marimoi';
                break;
            case 'diproses':
                $subject = 'Tanggapan Anda Sedang Kami Proses';
                break;
            case 'tindaklanjut': // kalau masih mau dipakai
                $subject = 'Tanggapan Anda di Marimoi Telah Kami Tindaklanjuti';
                break;
            case 'selesai':
                $subject = 'Tindak Lanjut Tanggapan Anda Telah Selesai';
                break;
            case 'ditolak':
                $subject = 'Mohon Maaf, Tanggapan Anda Tidak Dapat Kami Proses';
                break;
            case 'admin':
                $subject = '[Notifikasi Admin Marimoi] Tanggapan Baru dari Pengguna di Website Marimoi';
                break;
            default:
                $subject = 'Notifikasi dari Marimoi';
                break;
        }


        return $this->subject($subject)
                    ->view('emails.tanggapan')
                    ->with([
                        'data' => $this->data,
                        'type' => $this->type,
                    ]);
    }
}