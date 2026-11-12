<?php

namespace App\Http\Requests\Materi;

use App\Models\Materi;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

// Validasi formulir materi dari editor Quill
class SimpanMateriRequest extends FormRequest
{
    public function authorize(): Response|bool
    {
        return $this->route('materi') === null ? Gate::inspect('buat', Materi::class) : true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:255'],
            'ringkasan' => ['nullable', 'string'],
            'sampul' => ['nullable', 'string', 'max:255', $this->sampulDikenal()],
            'kategori' => ['required', 'array', 'min:1'],
            'kategori.*' => ['integer', 'exists:kategori,id'],
            'konten' => ['required', 'array'],
            'konten.ops' => ['required', 'array'],
            'konten_html' => ['required', 'string'],
            'catatan_revisi' => ['nullable', 'string'],
        ];
    }

    // Cek path sampul
    private function sampulDikenal(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($value !== null && $value !== '' && ! str_starts_with($value, 'sampul-materi/')) {
                $fail('Sampulnya tidak ketemu. Coba unggah ulang ya.');
            }
        };
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'judul.required' => 'Judulnya belum diisi.',
            'judul.max' => 'Judulnya kepanjangan, maksimal 255 karakter ya.',
            'kategori.required' => 'Pilih minimal satu kategori ya.',
            'kategori.array' => 'Kategorinya tidak ketemu. Coba pilih lagi ya.',
            'kategori.min' => 'Pilih minimal satu kategori ya.',
            'kategori.*.exists' => 'Kategorinya tidak ketemu. Coba pilih lagi ya.',
            'konten.required' => 'Isi materinya masih kosong.',
            'konten.ops.required' => 'Isi materinya masih kosong.',
            'konten_html.required' => 'Isi materinya masih kosong.',
        ];
    }
}