<?php
// app/Http/Requests/PublicationRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:255'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif', 'max:5120'], // 5MB max untuk cover
            'published_date' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ];

        if ($this->isMethod('POST')) {
            $rules['file'] = ['required', 'file', 'max:51200', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx']; // 50MB max
        } elseif ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['file'] = ['nullable', 'file', 'max:51200', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul publikasi wajib diisi.',
            'title.max' => 'Judul maksimal 255 karakter.',
            'file.required' => 'File publikasi wajib diupload.',
            'file.max' => 'Ukuran file maksimal 50MB.',
            'file.mimes' => 'Format file harus: PDF, DOC, DOCX, XLS, XLSX, PPT, atau PPTX.',
            'cover.image' => 'Cover harus berupa file gambar.',
            'cover.mimes' => 'Format cover harus: JPG, JPEG, PNG, atau GIF.',
            'cover.max' => 'Ukuran cover maksimal 5MB.',
            'published_date.date' => 'Format tanggal tidak valid.',
        ];
    }
}
