<?php

namespace App\Http\Requests\Program;

use App\Models\DokumentasiProgram;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Validasi satu butir dokumentasi program
class UnggahDokumentasiRequest extends FormRequest
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
        $aturan = [
            'jenis' => ['required', Rule::in([
                DokumentasiProgram::JENIS_FOTO,
                DokumentasiProgram::JENIS_VIDEO,
                DokumentasiProgram::JENIS_YOUTUBE,
            ])],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ];

        return match ($this->input('jenis')) {
            DokumentasiProgram::JENIS_FOTO => $aturan + [
                'berkas' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            ],
            DokumentasiProgram::JENIS_VIDEO => $aturan + [
                'berkas' => ['required', 'file', 'mimetypes:video/mp4', 'max:51200'],
            ],
            DokumentasiProgram::JENIS_YOUTUBE => $aturan + [
                'tautan' => [
                    'required',
                    'string',
                    'max:255',
                    function (string $attribute, mixed $value, Closure $fail) {
                        if (DokumentasiProgram::ambilIdYoutube((string) $value) === null) {
                            $fail('Tautan YouTube tidak dikenali. Salin ulang tautannya dari YouTube.');
                        }
                    },
                ],
            ],
            default => $aturan,
        };
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $video = $this->input('jenis') === DokumentasiProgram::JENIS_VIDEO;

        return [
            'jenis.required' => 'Jenis dokumentasi wajib dipilih.',
            'jenis.in' => 'Pilih jenis dokumentasi: foto, video, atau tautan YouTube.',
            'keterangan.max' => 'Keterangan maksimal 255 karakter.',
            'berkas.required' => $video ? 'Pilih berkas video.' : 'Pilih berkas foto.',
            'berkas.uploaded' => 'Berkas tidak berhasil diunggah. Kemungkinan ukurannya melebihi batas.',
            'berkas.image' => 'Berkas harus berupa gambar.',
            'berkas.mimes' => 'Foto harus berformat JPG atau PNG.',
            'berkas.mimetypes' => 'Video harus berformat MP4.',
            'berkas.max' => $video ? 'Ukuran video maksimal 50 MB.' : 'Ukuran foto maksimal 2 MB.',
            'tautan.required' => 'Tautan YouTube wajib diisi.',
            'tautan.max' => 'Tautan YouTube tidak dikenali. Salin ulang tautannya dari YouTube.',
        ];
    }
}
