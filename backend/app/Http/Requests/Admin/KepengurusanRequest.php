<?php

namespace App\Http\Requests\Admin;

use App\Models\Anggota;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'periode_id' => ['required', 'integer', 'exists:periode_kepengurusan,id'],
            'divisi_id' => ['nullable', 'integer', 'exists:divisi,id'],
            'jabatan_id' => ['required', 'integer', 'exists:jabatan,id'],
            'anggota_id' => [
                'required', 'integer', 'exists:anggota,id',
                // KP-07.4: satu anggota hanya sekali pada periode yang sama
                Rule::unique('kepengurusan', 'anggota_id')
                    ->where(fn ($query) => $query->where('periode_id', $this->input('periode_id')))
                    ->ignore($this->route('kepengurusan')),
                // KP-07.3: pengurus dipilih dari anggota aktif
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
            'periode_id.required' => 'Periode wajib dipilih.',
            'periode_id.exists' => 'Periode tidak ditemukan.',
            'divisi_id.exists' => 'Divisi tidak ditemukan.',
            'jabatan_id.required' => 'Jabatan wajib dipilih.',
            'jabatan_id.exists' => 'Jabatan tidak ditemukan.',
            'anggota_id.required' => 'Anggota wajib dipilih.',
            'anggota_id.exists' => 'Anggota tidak ditemukan.',
            'anggota_id.unique' => 'Anggota ini sudah tercatat pada periode tersebut.',
        ];
    }
}