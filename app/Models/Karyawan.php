<?php

namespace App\Models;

use App\Enums\JenisKelamin;
use App\Enums\StatusKaryawan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Karyawan extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /*
    | Stempel waktu tidak pernah ditampilkan, dan justru itu sumber bug tanggal.
    |
    | Kolom created_at/updated_at berupa Carbon; begitu model ini diserialisasi
    | ke prop Inertia, keduanya keluar sebagai "2026-03-31T17:00:00.000000Z" —
    | pukul 00:00 WIB 1 April yang terbaca sebagai 31 Maret. Tidak satu pun
    | halaman memakainya, jadi keduanya tidak ikut dikirim. Tanggal yang memang
    | dipakai layar diformat eksplisit di controller.
    |
    | @var array<int, string>
    */
    protected $hidden = ['created_at', 'updated_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jenis_kelamin' => JenisKelamin::class,
            'status' => StatusKaryawan::class,
            'tanggal_lahir' => 'date:Y-m-d',
        ];
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class, 'id_jabatan');
    }

    public function kontrakKaryawans(): HasMany
    {
        return $this->hasMany(KontrakKaryawan::class);
    }

    public function cashbons(): HasMany
    {
        return $this->hasMany(Cashbon::class);
    }

    public function penggajianDetails(): HasMany
    {
        return $this->hasMany(PenggajianDetail::class);
    }
}
