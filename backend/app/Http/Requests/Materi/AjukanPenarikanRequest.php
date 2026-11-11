<?php

namespace App\Http\Requests\Materi;

use App\Models\Materi;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Pengajuan penarikan materi dari publik oleh penulis (KF-20, KP-20.1).
 */
class AjukanPenarikanRequest extends FormRequest
{
    /**
     * Hanya penulis materi (MateriPolicy::tarik). Diperiksa di sini supaya
     * bukan penulis mendapat 403 lebih dulu, sebelum validasi alasan (422).
     */
    public function authorize(): Response
    {
        // inspect() mengembalikan Response sehingga pesan penolakan Policy ikut terkirim.
        return Gate::inspect('tarik', $this->route('materi'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'alasan' => ['required', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'alasan.required' => 'Tulis dulu alasannya ya.',
            'alasan.max' => 'Alasannya kepanjangan, maksimal 1.000 karakter ya.',
        ];
    }
}
