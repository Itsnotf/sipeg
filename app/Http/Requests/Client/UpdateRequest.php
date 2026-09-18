<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama_client' => ['required', 'string', 'max:255'],
            'alamat' => ['required', 'string', 'max:255'],
            // Aturan unique wajib mengecualikan client yang sedang diubah.
            // Tanpa itu, menyimpan client tanpa mengganti emailnya selalu
            // ditolak karena emailnya sendiri dianggap sudah terpakai.
            'email' => [
                'required', 'email',
                Rule::unique('clients', 'email')->ignore($this->route('client')),
            ],
            'no_hp' => ['required', 'string', 'max:15'],
            'deskripsi' => ['nullable', 'string'],
        ];
    }
}
