<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class ValidHCaptcha implements ValidationRule
{
    /**
     * hCaptcha dilewati saat APP_ENV=local agar form bisa diuji tanpa widget/koneksi hCaptcha.
     * Environment lain (production, staging, testing) tetap wajib captcha.
     */
    public static function isRequired(): bool
    {
        return ! app()->environment('local');
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string): void  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $response = Http::asForm()->post('https://hcaptcha.com/siteverify', [
            'secret' => config('services.hcaptcha.secret'),
            'response' => $value,
        ]);

        if (! $response->ok()) {
            $fail('Gagal menghubungi server hCaptcha.');

            return;
        }

        $result = $response->json();

        if (! ($result['success'] ?? false)) {
            $fail('CAPTCHA tidak valid.');
        }
    }
}
