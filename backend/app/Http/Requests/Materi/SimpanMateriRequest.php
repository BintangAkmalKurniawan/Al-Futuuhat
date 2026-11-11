<?php

namespace App\Http\Requests\Materi;

use App\Models\Materi;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Validasi formulir materi dari editor Quill (KF-08).
 * `konten` = Delta Quill (array), `konten_html` = hasil render editor yang akan disanitasi.
 */
class SimpanMateriRequest extends FormRequest
{
    public function authorize(): Response|bool
    {
        // Tambah materi: admin ditolak sebelum validasi (KP-08.6).
        // Ubah materi: kepemilikan dicek Policy di controller.
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
            // Sampul dikirim sebagai path hasil POST /unggah-sampul (KF-18).
            'sampul' => ['nullable', 'string', 'max:255', $this->sampulDikenal()],
            // Kategori wajib minimal satu, boleh lebih (KF-19, KP-19.2).
            'kategori' => ['required', 'array', 'min:1'],
            'kategori.*' => ['integer', 'exists:kategori,id'],
            'konten' => ['required', 'array'],
            'konten.ops' => ['required', 'array'],
            'konten_html' => ['required', 'string'],
            'catatan_revisi' => ['nullable', 'string'],
        ];
    }

    /**
     * Sampul hanya boleh berupa path di folder unggahan kita, supaya klien
     * tidak bisa menitipkan path sembarang (atau path berkas lain di server).
     */
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