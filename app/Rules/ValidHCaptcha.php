<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ValidHCaptcha implements ValidationRule
{

    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  \Closure(string): void  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $response = Http::asForm()->post('https://hcaptcha.com/siteverify', [
            'secret'   => config('services.hcaptcha.secret'),
            'response' => $value,
        ]);

        if (!$response->ok()) {
            $fail('Gagal menghubungi server hCaptcha.');
            return;
        }

        $result = $response->json();

        if (!($result['success'] ?? false)) {
            $fail('CAPTCHA tidak valid. Yang bukan manusia ga diajak :p.');
        }
    }
}
