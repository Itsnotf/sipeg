<?php

namespace App\Http\Requests\Cashbon;

use Illuminate\Foundation\Http\FormRequest;

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
            'karyawan_id' => ['required', 'exists:karyawans,id'],
            'jumlah' => ['required', 'numeric', 'min:1'],
            'keterangan' => ['required', 'string'],
        ];
    }
}
