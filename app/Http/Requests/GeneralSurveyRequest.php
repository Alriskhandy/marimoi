<?php
// app/Http/Requests/GeneralSurveyRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Survey;

class GeneralSurveyRequest extends FormRequest
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
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'feedback' => ['nullable', 'string', 'max:1000'],
            'suggestions' => ['nullable', 'string', 'max:1000'],
            'additional_data' => ['nullable', 'array'],
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
            'rating.required' => 'Rating wajib diberikan.',
            'rating.integer' => 'Rating harus berupa angka.',
            'rating.min' => 'Rating minimal 1.',
            'rating.max' => 'Rating maksimal 5.',
            'feedback.max' => 'Feedback maksimal 1000 karakter.',
            'suggestions.max' => 'Saran maksimal 1000 karakter.',
        ];
    }

    // Custom validation untuk mencegah spam survey
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (Survey::hasRecentSurvey($this->email, 24)) {
                $validator->errors()->add('email', 'Anda sudah mengisi survey dalam 24 jam terakhir.');
            }
        });
    }
}
