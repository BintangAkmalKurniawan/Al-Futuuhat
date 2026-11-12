<?php

namespace App\Http\Requests\Profil;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

// Ganti kata sandi sendiri
class GantiKataSandiRequest extends FormRequest
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
            'password_lama' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (! Hash::check((string) $value, $this->user()->password)) {
                        $fail('Kata sandi lama belum cocok.');
                    }
                },
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:password_lama'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password_lama.required' => 'Kata sandi lama belum diisi.',
            'password.required' => 'Kata sandi baru belum diisi.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
            'password.confirmed' => 'Ulangi kata sandi baru belum sama.',
            'password.different' => 'Kata sandi baru harus berbeda dari kata sandi lama.',
        ];
    }
}
