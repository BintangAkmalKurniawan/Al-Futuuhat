<?php

namespace App\Http\Requests\Program;

use App\Models\Anggota;
use App\Models\Program;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Validasi tambah/ubah program oleh admin
class SimpanProgramRequest extends FormRequest
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
        /** @var Program|null $program */
        $program = $this->route('program');

        return [
            'nama' => ['required', 'string', 'max:150', Rule::unique('program', 'nama')->ignore($program?->id)],
            'ringkasan' => ['nullable', 'string', 'max:300'],
            'jadwal' => ['nullable', 'string', 'max:150'],
            'status' => ['required', Rule::in([Program::STATUS_AKTIF, Program::STATUS_SELESAI])],
            'deskripsi' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (trim(strip_tags((string) $value, '<img>')) === '') {
                        $fail('Penjelasan program wajib diisi.');
                    }
                },
            ],
            'sampul' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'hapus_sampul' => ['sometimes', 'boolean'],
            'pic' => ['required', 'array', 'min:1'],
            'pic.*' => [
                'integer',
                'distinct',
                function (string $attribute, mixed $value, Closure $fail) {
                    $aktif = Anggota::whereKey($value)
                        ->whereHas('user', fn ($query) => $query->where('status', User::STATUS_AKTIF))
                        ->exists();

                    if (! $aktif) {
                        $fail('PIC harus anggota aktif.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama program wajib diisi.',
            'nama.max' => 'Nama program maksimal 150 karakter.',
            'nama.unique' => 'Nama program sudah ada.',
            'ringkasan.max' => 'Ringkasan maksimal 300 karakter.',
            'jadwal.max' => 'Jadwal maksimal 150 karakter.',
            'status.required' => 'Status program wajib dipilih.',
            'status.in' => 'Pilih status program: berjalan atau selesai.',
            'deskripsi.required' => 'Penjelasan program wajib diisi.',
            'sampul.image' => 'Berkas harus berupa gambar.',
            'sampul.mimes' => 'Sampul harus berformat JPG atau PNG.',
            'sampul.max' => 'Ukuran sampul maksimal 2 MB.',
            'pic.required' => 'Pilih minimal satu PIC.',
            'pic.min' => 'Pilih minimal satu PIC.',
            'pic.*.integer' => 'PIC yang dipilih tidak ditemukan.',
            'pic.*.distinct' => 'PIC tidak boleh dipilih dua kali.',
        ];
    }
}
