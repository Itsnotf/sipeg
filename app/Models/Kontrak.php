<?php

namespace App\Models;

use App\Enums\StatusKontrak;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kontrak extends Model
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
            'status' => StatusKontrak::class,
            'tanggal_mulai' => 'date:Y-m-d',
            'tanggal_selesai' => 'date:Y-m-d',
            'tanggal_gajian' => 'integer',
            'total_biaya' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function kontrakDokumens(): HasMany
    {
        return $this->hasMany(KontrakDokumen::class);
    }

    public function kontrakKaryawans(): HasMany
    {
        return $this->hasMany(KontrakKaryawan::class);
    }

    public function penggajians(): HasMany
    {
        return $this->hasMany(Penggajian::class);
    }
}
