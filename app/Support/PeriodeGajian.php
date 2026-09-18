<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Satu periode penggajian: sebuah bulan kalender, cakupan kerjanya yang sudah
 * dipotong ke rentang kontrak, dan kapan bulan itu dibayarkan.
 *
 * Pembagi prorata sengaja dipisahkan dari panjang cakupan. Cakupan bisa lebih
 * pendek dari sebulan karena kontrak mulai atau berakhir di tengah bulan,
 * sementara pembagi tetap sepanjang bulan penuh — sehingga pekerja yang hadir
 * sebulan penuh selalu menerima tepat satu gaji bulanan, tidak lebih.
 */
final readonly class PeriodeGajian
{
    public function __construct(
        public string $periode,
        public CarbonImmutable $mulai,
        public CarbonImmutable $selesai,
        public int $hariPeriode,
        public CarbonImmutable $tanggalBayar,
        public bool $final,
    ) {}

    /**
     * Jumlah hari seorang pekerja aktif dalam periode ini, dihitung inklusif.
     *
     * Penempatan tanpa tanggal dianggap berlaku sepanjang cakupan periode.
     * Tanggal di luar cakupan dijepit, sehingga penempatan yang melampaui
     * periode tidak pernah menghasilkan lebih dari panjang cakupannya.
     */
    public function hariAktif(?CarbonInterface $mulaiKerja, ?CarbonInterface $selesaiKerja): int
    {
        $awal = $mulaiKerja ? CarbonImmutable::parse($mulaiKerja)->startOfDay() : $this->mulai;
        $akhir = $selesaiKerja ? CarbonImmutable::parse($selesaiKerja)->startOfDay() : $this->selesai;

        $awal = $awal->max($this->mulai);
        $akhir = $akhir->min($this->selesai);

        if ($awal->greaterThan($akhir)) {
            return 0;
        }

        return (int) $awal->diffInDays($akhir) + 1;
    }

    public function proporsi(int $hariAktif): float
    {
        if ($this->hariPeriode <= 0) {
            return 0.0;
        }

        return $hariAktif / $this->hariPeriode;
    }

    public function jatuhTempo(?CarbonInterface $pada = null): bool
    {
        $pada = $pada ? CarbonImmutable::parse($pada) : CarbonImmutable::now();

        return $this->tanggalBayar->lessThanOrEqualTo($pada->startOfDay());
    }

    public function label(): string
    {
        return $this->mulai->translatedFormat('F Y');
    }
}
