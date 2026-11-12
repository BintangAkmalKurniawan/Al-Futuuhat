<?php

namespace App\Http\Requests\Admin;

use App\Models\VerifikasiMateri;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Keputusan admin atas pengajuan publikasi
class VerifikasiRevisiRequest extends FormRequest
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
            'keputusan' => [
                'required',
                Rule::in([VerifikasiMateri::KEPUTUSAN_DISETUJUI, VerifikasiMateri::KEPUTUSAN_DITOLAK]),
            ],
            'catatan' => ['nullable', 'string', 'required_if:keputusan,ditolak'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'keputusan.required' => 'Keputusan wajib dipilih.',
            'keputusan.in' => 'Pilih keputusan: setujui atau tolak.',
            'catatan.required_if' => 'Mohon isi catatan untuk penulis.',
        ];
    }
}