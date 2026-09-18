<?php

namespace App\Http\Requests\Karyawan;

use App\Enums\JenisKelamin;
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
    /*
    | Status karyawan tidak ada di sini dengan sengaja.
    |
    | Nilainya diturunkan dari penempatan, bukan diketik: menyetelnya
    | "Aktif" secara manual membuat pekerja hilang dari daftar pekerja
    | yang tersedia untuk ditempatkan, selamanya, tanpa petunjuk apa pun.
    | Satu-satunya penulis kolom itu adalah PenempatanKontrak.
    */
    public function rules(): array
    {
        return [
            'id_jabatan' => 'required|integer|exists:jabatans,id',
            'nama' => 'required|string|max:255',
            // Mengecualikan karyawan yang sedang diubah, agar menyimpan tanpa
            // mengganti NIK tidak ditolak sebagai duplikat dirinya sendiri
            'nik' => [
                'required', 'string', 'max:255',
                Rule::unique('karyawans', 'nik')->ignore($this->route('karyawan')),
            ],
            'alamat' => 'required|string',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => ['required', Rule::enum(JenisKelamin::class)],
            'no_hp' => 'required|string|max:15',
        ];
    }
}
