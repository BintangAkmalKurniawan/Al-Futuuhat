<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class AturUlangKataSandiRequest extends FormRequest
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
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'token.required' => 'Tautannya tidak lengkap. Coba minta tautan baru ya.',
            'email.required' => 'Tautannya tidak lengkap. Coba minta tautan baru ya.',
            'email.email' => 'Tautannya tidak lengkap. Coba minta tautan baru ya.',
            'password.required' => 'Kata sandi baru belum diisi.',
            'password.min' => 'Kata sandinya minimal 8 karakter ya.',
            'password.confirmed' => 'Kata sandinya belum sama. Coba ketik ulang ya.',
        ];
    }
}
