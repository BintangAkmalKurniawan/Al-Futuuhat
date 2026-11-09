<?php

namespace App\Http\Requests\Komentar;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Komentar forum: teks, gambar, atau keduanya (KP-14.2).
 */
class SimpanKomentarRequest extends FormRequest
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
            // Minimal salah satu: teks atau gambar.
            'isi' => ['nullable', 'string', 'required_without:gambar'],
            'gambar' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'parent_id' => ['nullable', 'integer', 'exists:komentar,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'isi.required_without' => 'Komentar harus berisi teks atau gambar.',
            'gambar.image' => 'Berkas harus berupa gambar.',
            'gambar.mimes' => 'Gambar harus berformat JPG atau PNG.',
            'gambar.max' => 'Ukuran gambar maksimal 2 MB.',
            'parent_id.exists' => 'Komentar induk tidak ditemukan.',
        ];
    }
}