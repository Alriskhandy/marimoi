<?php

namespace App\Http\Requests;

use App\Rules\ValidHCaptcha;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class DownloadSurveyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'organization' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'purpose' => ['required', 'string', 'min:10', 'max:1000'],
            // Buat hCaptcha optional dulu untuk debugging
            'h-captcha-response' => ['required', new ValidHCaptcha()],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap harus diisi.',
            'name.string' => 'Nama lengkap harus berupa teks.',
            'name.max' => 'Nama lengkap tidak boleh lebih dari 255 karakter.',

            'email.required' => 'Email harus diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email tidak boleh lebih dari 255 karakter.',

            'phone.string' => 'Nomor telepon harus berupa teks.',
            'phone.max' => 'Nomor telepon tidak boleh lebih dari 20 karakter.',

            'organization.string' => 'Nama organisasi harus berupa teks.',
            'organization.max' => 'Nama organisasi tidak boleh lebih dari 255 karakter.',

            'position.string' => 'Posisi harus berupa teks.',
            'position.max' => 'Posisi tidak boleh lebih dari 255 karakter.',

            'purpose.required' => 'Tujuan penggunaan harus diisi.',
            'purpose.string' => 'Tujuan penggunaan harus berupa teks.',
            'purpose.min' => 'Tujuan penggunaan minimal 10 karakter.',
            'purpose.max' => 'Tujuan penggunaan tidak boleh lebih dari 1000 karakter.',

            'h-captcha-response.string' => 'Response captcha tidak valid.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama lengkap',
            'email' => 'email',
            'phone' => 'nomor telepon',
            'organization' => 'organisasi',
            'position' => 'posisi/jabatan',
            'purpose' => 'tujuan penggunaan',
            'h-captcha-response' => 'captcha',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Bersihkan data input
        $this->merge([
            'name' => $this->sanitizeInput($this->name),
            'email' => $this->sanitizeEmail($this->email),
            'phone' => $this->sanitizeInput($this->phone),
            'organization' => $this->sanitizeInput($this->organization),
            'position' => $this->sanitizeInput($this->position),
            'purpose' => $this->sanitizeInput($this->purpose),
        ]);
    }

    /**
     * Sanitize general input
     */
    private function sanitizeInput($input)
    {
        if (!$input) return $input;

        return trim(strip_tags($input));
    }

    /**
     * Sanitize email input
     */
    private function sanitizeEmail($email)
    {
        if (!$email) return $email;

        return trim(strtolower(strip_tags($email)));
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        if ($this->expectsJson() || $this->ajax()) {
            // Return JSON response for AJAX requests
            throw new HttpResponseException(
                response()->json([
                    'success' => false,
                    'message' => 'Data yang Anda masukkan tidak valid.',
                    'errors' => $validator->errors()->all()
                ], 422)
            );
        }

        parent::failedValidation($validator);
    }

    /**
     * Get validated data with additional processing
     */
    public function getValidatedData(): array
    {
        $validated = $this->validated();

        // Tambahkan data tambahan
        $validated['ip_address'] = $this->ip();
        $validated['user_agent'] = $this->userAgent();
        $validated['downloaded_at'] = now();

        return $validated;
    }
}
