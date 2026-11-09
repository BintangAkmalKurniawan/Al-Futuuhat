<?php

namespace App\Http\Requests\Materi;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Unggahan gambar untuk editor Quill (KP-08.2, KNF-03).
 */
class UnggahGambarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'gambar' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'gambar.required' => 'Gambar wajib dipilih.',
            'gambar.image' => 'Berkas harus berupa gambar.',
            'gambar.mimes' => 'Gambar harus berformat JPG atau PNG.',
            'gambar.max' => 'Ukuran gambar maksimal 2 MB.',
        ];
    }
}