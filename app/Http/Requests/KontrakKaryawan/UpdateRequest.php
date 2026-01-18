<?php

namespace App\Http\Requests\KontrakKaryawan;

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
            'karyawan_id' => 'required|array|min:1',
            'karyawan_id.*' => 'required|exists:karyawans,id|distinct',
        ];
    }

    public function messages(): array
    {
        return [
            'karyawan_id.required' => 'Silakan pilih minimal satu karyawan',
            'karyawan_id.array' => 'Karyawan harus berupa array',
            'karyawan_id.min' => 'Silakan pilih minimal satu karyawan',
            'karyawan_id.*.required' => 'Setiap karyawan harus valid',
            'karyawan_id.*.exists' => 'Karyawan yang dipilih tidak valid',
            'karyawan_id.*.distinct' => 'Terdapat karyawan yang duplikat',
        ];
    }
}
