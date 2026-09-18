<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PenggajianDetail extends Model
{
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
     * `gaji_pokok_penuh`, `bpjs_persen`, `hari_aktif` dan `hari_periode` adalah
     * bahan beku perhitungan — disimpan agar nominal bisa dihitung ulang tanpa
     * bergantung pada data jabatan yang mungkin sudah berubah, dan agar setiap
     * angka di slip bisa ditelusuri asalnya.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gaji_pokok_penuh' => 'decimal:2',
            'gaji_pokok' => 'decimal:2',
            'bpjs_persen' => 'decimal:2',
            'bpjs' => 'decimal:2',
            'potongan_cashbon' => 'decimal:2',
            'total_gaji' => 'decimal:2',
            'hari_aktif' => 'integer',
            'hari_periode' => 'integer',
        ];
    }

    public function penggajian(): BelongsTo
    {
        return $this->belongsTo(Penggajian::class);
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class);
    }

    public function potongans(): HasMany
    {
        return $this->hasMany(CashbonPotongan::class);
    }
}
