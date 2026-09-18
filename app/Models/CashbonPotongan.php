<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris buku besar: cashbon X menyumbang Rp N pada baris detail
 * penggajian Y.
 *
 * Tanpa catatan ini sistem harus menebak saat sebuah cashbon dihapus atau
 * dilunasi, dan tebakan itulah yang dahulu mengurangi nominal dari setiap
 * slip pekerja sekaligus.
 */
class CashbonPotongan extends Model
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
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:2',
        ];
    }

    public function cashbon(): BelongsTo
    {
        return $this->belongsTo(Cashbon::class);
    }

    public function penggajianDetail(): BelongsTo
    {
        return $this->belongsTo(PenggajianDetail::class);
    }
}
