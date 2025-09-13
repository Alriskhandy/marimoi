<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AspirasiMail extends Mailable
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
                $subject = 'Terima kasih atas pengiriman Aspirasi Anda - Marimoi';
                break;
            case 'admin':
                $subject = '[Notifikasi Admin Marimoi] Aspirasi Baru Diterima';
                break;
            default:
                $subject = 'Notifikasi Aspirasi - Marimoi';
                break;
        }

        return $this->subject($subject)
            ->view('emails.aspirasi_mail')
            ->with([
                'data' => $this->data,
                'type' => $this->type,
            ]);
    }
}
