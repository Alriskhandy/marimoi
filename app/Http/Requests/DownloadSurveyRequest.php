<?php
// app/Http/Requests/DownloadSurveyRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ValidHCaptcha;

class DownloadSurveyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'organization' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'h-captcha-response' => ['required', new ValidHCaptcha()],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'name.max' => 'Nama maksimal 255 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal 255 karakter.',
            'phone.max' => 'Nomor telepon maksimal 20 karakter.',
            'organization.max' => 'Nama organisasi maksimal 255 karakter.',
            'position.max' => 'Posisi maksimal 255 karakter.',
            'purpose.max' => 'Tujuan download maksimal 255 karakter.',
            'h-captcha-response.required' => 'Verifikasi CAPTCHA wajib diselesaikan',
        ];
    }
}
