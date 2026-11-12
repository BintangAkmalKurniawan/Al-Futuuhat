<?php

namespace App\Http\Requests\Admin;

use App\Models\PenarikanMateri;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Keputusan admin atas pengajuan penarikan materi
class VerifikasiPenarikanRequest extends FormRequest
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
                Rule::in([PenarikanMateri::STATUS_DISETUJUI, PenarikanMateri::STATUS_DITOLAK]),
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
