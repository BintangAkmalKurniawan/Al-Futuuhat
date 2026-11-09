<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PeriodeRequest extends FormRequest
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
            'nama_periode' => ['required', 'string', 'max:255'],
            'tahun_mulai' => ['required', 'integer', 'min:1900', 'max:2100'],
            'tahun_selesai' => ['required', 'integer', 'gte:tahun_mulai', 'max:2100'],
            'is_aktif' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_periode.required' => 'Nama periode wajib diisi.',
            'tahun_mulai.required' => 'Tahun mulai wajib diisi.',
            'tahun_selesai.required' => 'Tahun selesai wajib diisi.',
            'tahun_selesai.gte' => 'Tahun selesai tidak boleh lebih awal dari tahun mulai.',
        ];
    }
}