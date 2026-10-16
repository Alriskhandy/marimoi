<?php

namespace App\Http\Requests;

use App\Models\DocumentTemplate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class DocumentTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('document-templates.manage') ?? false;
    }

    /**
     * Kotak centang yang tidak dicentang tidak terkirim; jadikan false eksplisit.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(collect(['header_enabled', 'for_map', 'for_analysis', 'is_active', 'is_default', 'remove_logo_left', 'remove_logo_right'])
            ->mapWithKeys(fn (string $field) => [$field => $this->boolean($field)])
            ->all());

        // Tata letak dikirim editor sebagai JSON di input tersembunyi.
        if (is_string($this->input('layout'))) {
            $this->merge(['layout' => json_decode($this->input('layout'), true)]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $logo = ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024', 'dimensions:max_width=2000,max_height=2000'];

        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'header_enabled' => ['boolean'],
            'header_line1' => ['nullable', 'string', 'max:150'],
            'header_line2' => ['nullable', 'string', 'max:150'],
            'header_line3' => ['nullable', 'string', 'max:500'],
            'logo_left' => $logo,
            'logo_right' => $logo,
            'remove_logo_left' => ['boolean'],
            'remove_logo_right' => ['boolean'],
            'accent_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'orientation' => ['required', 'in:portrait,landscape'],
            'layout' => ['nullable', 'array'],
            'layout.*' => ['array'],
            'layout.*.enabled' => ['boolean'],
            'layout.*.x' => ['numeric', 'between:0,100'],
            'layout.*.y' => ['numeric', 'between:0,100'],
            'layout.*.w' => ['numeric', 'between:'.DocumentTemplate::LAYOUT_MIN_SIZE.',100'],
            'layout.*.h' => ['numeric', 'between:'.DocumentTemplate::LAYOUT_MIN_SIZE.',100'],
            'footer_text' => ['nullable', 'string', 'max:500'],
            'for_map' => ['boolean'],
            'for_analysis' => ['boolean'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->boolean('for_map') && ! $this->boolean('for_analysis')) {
                $validator->errors()->add('for_map', 'Pilih minimal satu penggunaan: Unduh Peta atau Analisis Peta.');
            }
            if ($this->boolean('is_default') && ! $this->boolean('is_active')) {
                $validator->errors()->add('is_default', 'Template bawaan harus aktif.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama template wajib diisi.',
            'accent_color.regex' => 'Warna aksen harus berformat #RRGGBB.',
            'orientation.in' => 'Orientasi harus potret atau lanskap.',
            'layout.*.*.between' => 'Posisi atau ukuran elemen tata letak di luar halaman.',
            'logo_left.image' => 'Logo kiri harus berupa gambar.',
            'logo_right.image' => 'Logo kanan harus berupa gambar.',
            'logo_left.max' => 'Ukuran logo kiri maksimal 1 MB.',
            'logo_right.max' => 'Ukuran logo kanan maksimal 1 MB.',
            'logo_left.dimensions' => 'Dimensi logo kiri maksimal 2000 × 2000 piksel.',
            'logo_right.dimensions' => 'Dimensi logo kanan maksimal 2000 × 2000 piksel.',
        ];
    }
}
