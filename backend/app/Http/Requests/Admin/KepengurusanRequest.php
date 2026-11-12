<?php

namespace App\Http\Requests\Admin;

use App\Models\Anggota;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

// Tambah/ubah baris susunan pengurus
class KepengurusanRequest extends FormRequest
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
            'divisi_id' => ['nullable', 'integer', 'exists:divisi,id'],
            'jabatan_id' => ['required', 'integer', 'exists:jabatan,id'],
            'anggota_id' => [
                'required', 'integer', 'exists:anggota,id',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $anggota = Anggota::with('user')->find($value);

                    if ($anggota === null || $anggota->user?->status !== User::STATUS_AKTIF) {
                        $fail('Hanya anggota aktif yang dapat dijadikan pengurus.');
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
            'divisi_id.exists' => 'Divisi tidak ditemukan.',
            'jabatan_id.required' => 'Jabatan wajib dipilih.',
            'jabatan_id.exists' => 'Jabatan tidak ditemukan.',
            'anggota_id.required' => 'Anggota wajib dipilih.',
            'anggota_id.exists' => 'Anggota tidak ditemukan.',
        ];
    }
}
