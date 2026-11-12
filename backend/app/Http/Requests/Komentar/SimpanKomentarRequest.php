<?php

namespace App\Http\Requests\Komentar;

use Illuminate\Foundation\Http\FormRequest;

// Komentar forum
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
            'isi.required_without' => 'Tulis komentar atau lampirkan gambar dulu ya.',
            'gambar.image' => 'Lampirannya harus berupa gambar ya.',
            'gambar.mimes' => 'Gambarnya harus JPG atau PNG ya.',
            'gambar.max' => 'Gambarnya kebesaran, maksimal 2 MB ya.',
            'parent_id.exists' => 'Komentar yang mau kamu balas tidak ketemu. Mungkin sudah dihapus.',
        ];
    }
}