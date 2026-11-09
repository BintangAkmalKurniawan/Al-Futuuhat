<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifikasiPendaftaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // hak akses admin dicek middleware
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'keputusan' => ['required', Rule::in(['disetujui', 'ditolak'])],
            // KP-05.4: alasan penolakan wajib
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
            'keputusan.in' => 'Keputusan harus disetujui atau ditolak.',
            'catatan.required_if' => 'Catatan wajib diisi saat menolak pendaftaran.',
        ];
    }
}