<?php

namespace App\Http\Requests\Profil;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Ubah profil sendiri (KF-24). Email tidak dapat diubah.
 * Nomor HP dan alamat hanya wajib bila akun punya data keanggotaan.
 */
class UbahProfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // selalu profil milik pengguna yang sedang masuk
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $punyaAnggota = $this->user()->anggota()->exists();

        return [
            'nama' => ['required', 'string', 'max:255'],
            'no_hp' => [Rule::requiredIf($punyaAnggota), 'nullable', 'string', 'max:20'],
            'alamat' => [Rule::requiredIf($punyaAnggota), 'nullable', 'string'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'hapus_foto' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Pesan netral karena halaman profil dipakai anggota maupun admin.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama belum diisi.',
            'nama.max' => 'Nama terlalu panjang. Maksimal 255 karakter.',
            'no_hp.required' => 'Nomor HP belum diisi.',
            'no_hp.max' => 'Nomor HP terlalu panjang. Maksimal 20 karakter.',
            'alamat.required' => 'Alamat belum diisi.',
            'foto.image' => 'Foto harus berupa gambar.',
            'foto.mimes' => 'Foto harus berformat JPG atau PNG.',
            'foto.max' => 'Foto terlalu besar. Ukuran maksimal 2 MB.',
        ];
    }
}
