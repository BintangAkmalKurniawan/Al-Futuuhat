<?php

namespace App\Http\Requests\Materi;

use App\Models\Materi;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

// Unggahan gambar sampul materi
class UnggahSampulRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('buat', Materi::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sampul' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sampul.required' => 'Pilih gambar sampulnya dulu ya.',
            'sampul.image' => 'Berkasnya harus berupa gambar ya.',
            'sampul.mimes' => 'Sampulnya harus JPG atau PNG ya.',
            'sampul.max' => 'Sampulnya kebesaran, maksimal 2 MB ya.',
        ];
    }
}
