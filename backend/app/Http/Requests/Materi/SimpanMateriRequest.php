<?php

namespace App\Http\Requests\Materi;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi formulir materi dari editor Quill (KF-08).
 * `konten` = Delta Quill (array), `konten_html` = hasil render editor yang akan disanitasi.
 */
class SimpanMateriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // hak penulis dicek lewat Policy
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:255'],
            'ringkasan' => ['nullable', 'string'],
            'konten' => ['required', 'array'],
            'konten.ops' => ['required', 'array'],
            'konten_html' => ['required', 'string'],
            'catatan_revisi' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'judul.required' => 'Judul wajib diisi.',
            'judul.max' => 'Judul maksimal 255 karakter.',
            'konten.required' => 'Isi materi wajib diisi.',
            'konten.ops.required' => 'Isi materi wajib diisi.',
            'konten_html.required' => 'Isi materi wajib diisi.',
        ];
    }
}