<?php

namespace Database\Factories;

use App\Mail\AspirasiMail;
use App\Mail\TanggapanMail;
use App\Models\MailLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MailLog>
 */
class MailLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => MailLog::STATUS_SENT,
            'mailable' => fake()->randomElement([AspirasiMail::class, TanggapanMail::class]),
            'subject' => 'Aspirasi Anda Telah Kami Terima (MARIMOI-ASP-'.now()->format('Ymd').'-'.fake()->numerify('####').')',
            'recipients' => fake()->safeEmail(),
            'mailer' => 'smtp',
            'message_id' => fake()->uuid().'@marimoi.test',
            'error' => null,
        ];
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => MailLog::STATUS_FAILED,
            'message_id' => null,
            'error' => 'Connection could not be established with host "smtp.example.com:465"',
        ]);
    }
}
