<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

// Validasi formulir pendaftaran anggota
class RegisterRequest extends FormRequest
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
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'no_hp' => ['required', 'string', 'max:20'],
            'alamat' => ['required', 'string'],
            'alasan_bergabung' => ['required', 'string'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }

    /**
     * Pesan validasi
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama belum diisi.',
            'email.required' => 'Email belum diisi.',
            'email.email' => 'Format email-nya belum benar. Coba cek lagi ya.',
            'email.unique' => 'Email ini sudah terdaftar. Coba masuk saja, atau pakai email lain.',
            'password.required' => 'Kata sandi belum diisi.',
            'password.min' => 'Kata sandinya minimal 8 karakter ya.',
            'password.confirmed' => 'Kata sandinya belum sama. Coba ketik ulang ya.',
            'no_hp.required' => 'Nomor HP belum diisi.',
            'alamat.required' => 'Alamat belum diisi.',
            'alasan_bergabung.required' => 'Ceritakan dulu alasanmu bergabung ya.',
            'foto.image' => 'Fotonya harus berupa gambar ya.',
            'foto.mimes' => 'Fotonya harus JPG atau PNG ya.',
            'foto.max' => 'Fotonya kebesaran, maksimal 2 MB ya.',
        ];
    }
}