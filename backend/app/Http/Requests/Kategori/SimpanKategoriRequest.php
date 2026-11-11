<?php

namespace App\Http\Requests\Kategori;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi formulir kategori materi (KF-19, KP-19.1).
 * Admin hanya mengisi nama; slug dibentuk otomatis dari nama.
 */
class SimpanKategoriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // hak admin dicek lewat middleware `admin`
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama' => [
                'required', 'string', 'max:255',
                Rule::unique('kategori', 'nama')->ignore($this->route('kategori')),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama kategori wajib diisi.',
            'nama.max' => 'Nama kategori maksimal 255 karakter.',
            'nama.unique' => 'Nama kategori sudah ada.',
        ];
    }
}
