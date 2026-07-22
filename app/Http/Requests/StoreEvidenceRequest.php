<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => [
                'required',
                'file',
                'max:10240', // 10 MB
                'mimes:jpg,jpeg,png,webp',
                'dimensions:max_width=10000,max_height=10000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'File gambar wajib dipilih.',
            'image.file' => 'Field harus berupa file.',
            'image.max' => 'Ukuran gambar maksimal 10 MB.',
            'image.mimes' => 'Format yang didukung: JPG, JPEG, PNG, WebP.',
            'image.dimensions' => 'Dimensi gambar tidak valid.',
        ];
    }
}
