<?php

namespace App\Http\Requests\Beranda;

use Illuminate\Foundation\Http\FormRequest;

// Validasi konten beranda yang diatur admin
class SimpanBerandaRequest extends FormRequest
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
            'cerita_judul' => ['nullable', 'string', 'max:150'],
            'cerita_isi' => ['nullable', 'string'],
            'visi' => ['nullable', 'string', 'max:1000'],
            'misi' => ['nullable', 'string', 'max:3000'],
            'kontak_alamat' => ['nullable', 'string', 'max:255'],
            'kontak_whatsapp' => ['nullable', 'string', 'regex:/^[0-9+\s-]{8,20}$/'],
            'kontak_email' => ['nullable', 'email', 'max:255'],
            'kontak_instagram' => ['nullable', 'string', 'max:50', 'regex:/^@?[A-Za-z0-9._]+$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cerita_judul.max' => 'Judul cerita maksimal 150 karakter.',
            'visi.max' => 'Visi maksimal 1.000 karakter.',
            'misi.max' => 'Misi maksimal 3.000 karakter.',
            'kontak_alamat.max' => 'Alamat maksimal 255 karakter.',
            'kontak_whatsapp.regex' => 'Nomor WhatsApp hanya boleh berisi angka, 8 sampai 20 digit.',
            'kontak_email.email' => 'Format email belum benar.',
            'kontak_instagram.regex' => 'Nama akun Instagram hanya boleh berisi huruf, angka, titik, dan garis bawah.',
            'kontak_instagram.max' => 'Nama akun Instagram maksimal 50 karakter.',
        ];
    }
}
