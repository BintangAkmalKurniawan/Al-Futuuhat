<?php

namespace App\Http\Requests\Materi;

use Illuminate\Foundation\Http\FormRequest;

// Unggahan gambar untuk editor Quill
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
            'gambar.required' => 'Pilih gambarnya terlebih dahulu.',
            'gambar.image' => 'Berkas harus berupa gambar.',
            'gambar.mimes' => 'Gambar harus berformat JPG atau PNG.',
            'gambar.max' => 'Gambar terlalu besar. Ukuran maksimal 2 MB.',
        ];
    }
}