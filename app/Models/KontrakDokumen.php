<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KontrakDokumen extends Model
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

    public function kontrak()
    {
        return $this->belongsTo(Kontrak::class);
    }
}
