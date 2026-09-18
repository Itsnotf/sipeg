<?php

namespace App\Http\Requests\RoleRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
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
            // Nama peran wajib unik, mengecualikan peran yang sedang diubah.
            // Sebelumnya aturan unique tidak ada sama sekali, sehingga dua peran
            // bisa bernama sama dan uji duplikatnya selalu gagal.
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('roles', 'name')->ignore($this->route('role')),
            ],
            // Peran tanpa izin sah — itu peran yang belum diberi hak apa pun,
            // bukan masukan yang salah.
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }
}
