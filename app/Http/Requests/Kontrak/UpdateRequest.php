<?php

namespace App\Http\Requests\Kontrak;

use App\Enums\StatusKontrak;
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
            'client_id' => 'required|exists:clients,id',
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'total_biaya' => ['required', 'numeric', 'min:0'],
            'tanggal_mulai' => 'required|date',
            'tanggal_gajian' => ['required', 'integer', 'between:1,31'],
            'tanggal_selesai' => ['required', 'date', 'after:tanggal_mulai'],
            'status' => ['required', Rule::enum(StatusKontrak::class)],
        ];
    }
}
