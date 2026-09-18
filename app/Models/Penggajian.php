<?php

namespace App\Models;

use App\Enums\StatusPenggajian;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Penggajian extends Model
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
     * `periode` sengaja tetap string 'Y-m-d' berisi tanggal 1 bulan yang
     * dibayar. Ia adalah identitas periode, bukan tanggal pembayaran, dan
     * urutannya sudah benar secara leksikografis.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StatusPenggajian::class,
            'periode_mulai' => 'date:Y-m-d',
            'periode_selesai' => 'date:Y-m-d',
            'tanggal_bayar' => 'date:Y-m-d',
            'total_gaji' => 'decimal:2',
            'final' => 'boolean',
        ];
    }

    public function kontrak(): BelongsTo
    {
        return $this->belongsTo(Kontrak::class);
    }

    public function penggajianDetails(): HasMany
    {
        return $this->hasMany(PenggajianDetail::class);
    }

    /**
     * Penggajian yang sudah dibayar bersifat immutable.
     */
    public function terkunci(): bool
    {
        return $this->status instanceof StatusPenggajian && $this->status->terkunci();
    }

    public function scopeBelumDibayar(Builder $query): Builder
    {
        return $query->where('status', StatusPenggajian::BelumDibayar);
    }
}
