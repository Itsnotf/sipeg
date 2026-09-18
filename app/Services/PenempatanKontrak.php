<?php

namespace App\Services;

use App\Enums\StatusKaryawan;
use App\Models\Karyawan;
use App\Models\Kontrak;
use App\Models\KontrakKaryawan;
use App\Models\Penggajian;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Siklus hidup penempatan pekerja pada kontrak.
 *
 * Dikumpulkan di satu tempat karena sebelumnya bercabang dua dan kedua cabang
 * tidak sama. Melepas pekerja satu per satu menutup penempatannya dengan benar,
 * sedangkan menandai kontrak "Selesai" hanya mengubah status pekerjanya menjadi
 * Non Aktif dan membiarkan `tanggal_selesai` penempatannya kosong.
 *
 * Akibatnya pekerja itu muncul di daftar "tersedia" — daftar itu menyaring
 * status Non Aktif — lalu ditolak saat disimpan karena penempatannya masih
 * terhitung aktif di kontrak lain. Tidak ada jalan keluar dari layar kontrak.
 */
final class PenempatanKontrak
{
    public function __construct(private PenggajianService $penggajian) {}

    /**
     * Mengakhiri satu penempatan pada tanggal tertentu.
     *
     * Penempatan diakhiri, bukan dihapus: barisnya masih dibutuhkan untuk
     * menghitung hari yang benar-benar sudah dikerjakan pada periode berjalan.
     */
    public function akhiri(KontrakKaryawan $penempatan, ?CarbonInterface $pada = null): void
    {
        DB::transaction(function () use ($penempatan, $pada): void {
            $penempatan->update([
                'tanggal_selesai' => $this->tanggalAkhir($penempatan, $pada)->format('Y-m-d'),
            ]);

            $this->segarkanStatusKaryawan($penempatan->karyawan_id);

            $this->susunUlangBelumDibayar($penempatan->kontrak()->firstOrFail());
        });
    }

    /**
     * Mengakhiri seluruh penempatan yang masih berjalan pada sebuah kontrak.
     */
    public function akhiriSeluruhnya(Kontrak $kontrak, ?CarbonInterface $pada = null): void
    {
        $penempatans = KontrakKaryawan::aktif()->where('kontrak_id', $kontrak->id)->get();

        if ($penempatans->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($kontrak, $penempatans, $pada): void {
            foreach ($penempatans as $penempatan) {
                $penempatan->update([
                    'tanggal_selesai' => $this->tanggalAkhir($penempatan, $pada, $kontrak)->format('Y-m-d'),
                ]);
            }

            foreach ($penempatans->pluck('karyawan_id')->unique() as $karyawanId) {
                $this->segarkanStatusKaryawan((int) $karyawanId);
            }

            $this->susunUlangBelumDibayar($kontrak);
        });
    }

    /**
     * Roster berubah, sehingga penggajian yang belum dibayar harus disusun ulang
     * agar ikut memuat atau melepas pekerja bersangkutan. Penggajian yang sudah
     * dibayar tidak disentuh.
     */
    public function susunUlangBelumDibayar(Kontrak $kontrak): void
    {
        $penggajians = Penggajian::belumDibayar()
            ->where('kontrak_id', $kontrak->id)
            ->orderBy('periode')
            ->get();

        foreach ($penggajians as $penggajian) {
            $this->penggajian->hitungUlang($penggajian);
        }
    }

    /**
     * Hari terakhir pekerja benar-benar bekerja.
     *
     * Dijepit di antara tanggal mulai penempatan dan akhir kontrak: menandai
     * kontrak yang sudah lewat sebagai Selesai hari ini tidak boleh membuat
     * pekerjanya seolah bekerja sampai hari ini.
     */
    private function tanggalAkhir(
        KontrakKaryawan $penempatan,
        ?CarbonInterface $pada,
        ?Kontrak $kontrak = null,
    ): CarbonImmutable {
        $kontrak ??= $penempatan->kontrak()->firstOrFail();

        $akhir = ($pada ? CarbonImmutable::parse($pada) : CarbonImmutable::now())->startOfDay();

        if ($kontrak->tanggal_selesai !== null) {
            $akhir = $akhir->min(CarbonImmutable::parse($kontrak->tanggal_selesai)->startOfDay());
        }

        if ($penempatan->tanggal_mulai !== null) {
            $akhir = $akhir->max(CarbonImmutable::parse($penempatan->tanggal_mulai)->startOfDay());
        }

        return $akhir;
    }

    /**
     * Status karyawan diturunkan dari penempatannya, tidak ditetapkan manual.
     */
    private function segarkanStatusKaryawan(int $karyawanId): void
    {
        $masihBekerja = KontrakKaryawan::aktif()->where('karyawan_id', $karyawanId)->exists();

        Karyawan::whereKey($karyawanId)->update([
            'status' => $masihBekerja ? StatusKaryawan::Aktif : StatusKaryawan::NonAktif,
        ]);
    }
}
