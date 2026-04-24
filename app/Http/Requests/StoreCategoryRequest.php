<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    /**
     * Izinkan user yang sudah login untuk melakukan request ini.
     */
    public function authorize(): bool
    {
        return true; 
    }

    /**
     * Aturan validasi untuk data kategori.
     */
    public function rules(): array
    {
        return [
            'category_name' => ['required', 'string', 'max:255'],
            'type_id' => ['required', 'exists:transaction_types,id'],
            'icon' => ['nullable', 'string', 'max:255'],
            'keyword' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Custom pesan error (opsional).
     */
    public function messages(): array
    {
        return [
            'category_name.required' => 'Nama kategori wajib diisi.',
            'type_id.required' => 'Tipe transaksi harus dipilih.',
            'type_id.exists' => 'Tipe transaksi tidak valid.',
        ];
    }
}