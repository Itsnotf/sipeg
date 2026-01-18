<?php

namespace App\Http\Requests\Jabatan;

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
            'nama_jabatan'=> 'required|max:255',
            'deskripsi'=> 'required|max:255',
            'gaji'=> 'required|string',
            'bpjs'=> 'required|string',

        ];
    }
}
