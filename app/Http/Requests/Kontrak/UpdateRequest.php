<?php

namespace App\Http\Requests\Kontrak;

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
            'client_id' => 'required|exists:clients,id',
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'total_biaya' => 'required|string',
            'tanggal_mulai' => 'required|date',
            'tanggal_gajian' => 'required',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'status' => 'required|string|max:255',
        ];
    }
}
