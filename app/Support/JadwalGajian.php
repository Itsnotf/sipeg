<?php

namespace App\Support;

use App\Models\Kontrak;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Menurunkan jadwal penggajian sebuah kontrak.
 *
 * Satu periode sama dengan satu bulan kalender. `tanggal_gajian` hanya
 * menentukan KAPAN bulan itu dibayarkan, tidak memengaruhi nominalnya —
 * pembayaran jatuh pada tanggal tersebut di bulan BERIKUTNYA, setelah bulan
 * kerjanya benar-benar selesai, sehingga tidak ada hari yang dibayar di muka.
 *
 * Kelas ini murni: tidak menyentuh basis data dan tidak menulis apa pun.
 */
final class JadwalGajian
{
    private bool $pakaiHariKalender;

    private int $hariTetap;

    public function __construct(?bool $pakaiHariKalender = null, ?int $hariTetap = null)
    {
        $this->pakaiHariKalender = $pakaiHariKalender ?? (bool) config('payroll.hari_per_bulan_kalender', true);
        $this->hariTetap = $hariTetap ?? (int) config('payroll.hari_per_bulan_tetap', 30);
    }

    /**
     * @return array<int, PeriodeGajian>
     */
    public function untuk(Kontrak $kontrak): array
    {
        $hariGajian = (int) $kontrak->tanggal_gajian;

        if ($hariGajian < 1 || $hariGajian > 31) {
            throw new InvalidArgumentException(
                "Tanggal gajian harus antara 1 dan 31. Nilai yang diterima: {$hariGajian}."
            );
        }

        $mulai = CarbonImmutable::parse($kontrak->tanggal_mulai)->startOfDay();
        $selesai = CarbonImmutable::parse($kontrak->tanggal_selesai)->startOfDay();

        if ($selesai->lessThan($mulai)) {
            throw new InvalidArgumentException(
                'Tanggal selesai kontrak tidak boleh mendahului tanggal mulai.'
            );
        }

        $periodes = [];

        // Kursor selalu berada di tanggal 1, sehingga addMonth() tidak pernah
        // melompati Februari sebagaimana terjadi bila menambah bulan pada tanggal 31.
        $kursor = $mulai->startOfMonth();

        while ($kursor->lessThanOrEqualTo($selesai)) {
            $akhirBulan = $kursor->endOfMonth()->startOfDay();
            $cakupanSelesai = $akhirBulan->min($selesai);

            $bulanBayar = $kursor->addMonth();

            $periodes[] = new PeriodeGajian(
                periode: $kursor->format('Y-m-d'),
                mulai: $kursor->max($mulai),
                selesai: $cakupanSelesai,
                hariPeriode: $this->pakaiHariKalender ? $akhirBulan->day : $this->hariTetap,
                tanggalBayar: $bulanBayar->day(min($hariGajian, $bulanBayar->daysInMonth)),
                final: $cakupanSelesai->equalTo($selesai),
            );

            $kursor = $kursor->addMonth();
        }

        return $periodes;
    }

    /**
     * Periode yang tanggal bayarnya sudah tiba.
     *
     * @return array<int, PeriodeGajian>
     */
    public function jatuhTempo(Kontrak $kontrak, ?CarbonInterface $pada = null): array
    {
        return array_values(array_filter(
            $this->untuk($kontrak),
            fn (PeriodeGajian $periode): bool => $periode->jatuhTempo($pada)
        ));
    }

    /**
     * Periode tertentu berdasarkan kuncinya, atau null bila tidak ada dalam jadwal.
     */
    public function cari(Kontrak $kontrak, string $periode): ?PeriodeGajian
    {
        foreach ($this->untuk($kontrak) as $kandidat) {
            if ($kandidat->periode === $periode) {
                return $kandidat;
            }
        }

        return null;
    }
}
