<?php

namespace App\Models;

use App\Enums\StatusCashbon;
use App\Enums\StatusPenggajian;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cashbon extends Model
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
            'jumlah' => 'decimal:2',
            'status' => StatusCashbon::class,
        ];
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class);
    }

    /**
     * Buku besar alokasi: setiap baris mencatat berapa rupiah cashbon ini
     * menyumbang ke satu baris detail penggajian. Inilah sumber kebenaran
     * potongan — bukan angka yang ditulis ulang di detail penggajian.
     */
    public function potongans(): HasMany
    {
        return $this->hasMany(CashbonPotongan::class);
    }

    /**
     * Total yang sudah dialokasikan. Memakai hasil withSum() bila tersedia
     * agar tidak memicu query per baris saat menampilkan daftar.
     */
    public function terpotong(): float
    {
        if (array_key_exists('potongans_sum_jumlah', $this->attributes)) {
            return (float) $this->attributes['potongans_sum_jumlah'];
        }

        if ($this->relationLoaded('potongans')) {
            return (float) $this->potongans->sum('jumlah');
        }

        return (float) $this->potongans()->sum('jumlah');
    }

    /**
     * Sisa yang belum dialokasikan ke slip mana pun — yakni berapa lagi yang
     * boleh dipotong pada periode berikutnya. Dipakai saat mengalokasi agar
     * satu hutang tidak dipotong dua kali di dua periode.
     */
    public function sisa(): float
    {
        return max(0.0, (float) $this->jumlah - $this->terpotong());
    }

    /**
     * Bagian yang sudah benar-benar dipotong dari gaji yang telah dibayarkan.
     *
     * Alokasi pada penggajian yang belum dibayar hanyalah pemesanan: uangnya
     * belum berpindah dan masih bisa dilepas kembali.
     */
    public function terbayar(): float
    {
        return (float) $this->potongans()
            ->whereHas(
                'penggajianDetail.penggajian',
                fn ($query) => $query->where('status', StatusPenggajian::Dibayar)
            )
            ->sum('jumlah');
    }

    /**
     * Hutang yang sesungguhnya masih ditanggung pekerja.
     */
    public function sisaHutang(): float
    {
        return max(0.0, (float) $this->jumlah - $this->terbayar());
    }

    public function scopeBerjalan(Builder $query): Builder
    {
        return $query->where('status', StatusCashbon::Berjalan);
    }
}
