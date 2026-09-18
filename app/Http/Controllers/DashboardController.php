<?php

namespace App\Http\Controllers;

use App\Enums\StatusKontrak;
use App\Enums\StatusPenggajian;
use App\Models\Cashbon;
use App\Models\Karyawan;
use App\Models\Kontrak;
use App\Models\Penggajian;
use App\Models\PenggajianDetail;
use App\Support\JadwalGajian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class DashboardController extends Controller
{
    public function __construct(private JadwalGajian $jadwal) {}

    public function index(Request $request)
    {
        /*
        | Dashboard tetap terbuka bagi siapa pun yang berhasil masuk — ia
        | halaman pendarat — tetapi tiap blok angkanya mensyaratkan izin
        | modulnya sendiri. Sebelumnya nilai kontrak, margin per kontrak, sisa
        | hutang, dan nama pekerja terlihat oleh pengguna tanpa izin apa pun.
        */
        $bolehKontrak = $request->user()?->can('kontraks index') ?? false;
        $bolehCashbon = $request->user()?->can('cashbons index') ?? false;
        $bolehPenggajian = $request->user()?->can('penggajians index') ?? false;

        // Sebelumnya menghitung status 'aktif', padahal formulir kontrak hanya
        // pernah menulis Pending/Progres/Selesai — angkanya selalu nol.
        $kontrakBerjalan = Kontrak::where('status', StatusKontrak::Progres)->count();

        // Dijumlahkan di sisi basis data. Sebelumnya seluruh baris penggajian
        // beserta detailnya dimuat ke memori hanya untuk menjumlah satu kolom,
        // dua kali setiap halaman dibuka.
        $totalBiaya = $bolehKontrak ? (float) Kontrak::sum('total_biaya') : 0.0;
        $totalGaji = $bolehPenggajian ? (float) PenggajianDetail::sum('total_gaji') : 0.0;

        return inertia('dashboard', [
            'statistics' => [
                'total_kontraks' => $bolehKontrak ? Kontrak::count() : 0,
                'active_kontraks' => $bolehKontrak ? $kontrakBerjalan : 0,
                'total_karyawans' => ($request->user()?->can('karyawans index') ?? false) ? Karyawan::count() : 0,
                'total_penggajians' => $bolehPenggajian ? Penggajian::count() : 0,
            ],
            'financial' => [
                'total_biaya_kontraks' => $totalBiaya,
                'total_penggajian_dikeluarkan' => $totalGaji,
                'keuntungan_bersih' => $totalBiaya - $totalGaji,
            ],
            'penggajian_status' => [
                'pending_count' => $bolehPenggajian ? Penggajian::where('status', StatusPenggajian::BelumDibayar)->count() : 0,
                'pending_total_gaji' => $bolehPenggajian
                    ? (float) Penggajian::where('status', StatusPenggajian::BelumDibayar)->sum('total_gaji')
                    : 0.0,
            ],
            'cashbon_status' => $bolehCashbon ? $this->ringkasanCashbon() : $this->ringkasanCashbonKosong(),
            'margin_kontraks' => $bolehKontrak ? $this->marginPerKontrak() : [],
            'jadwal_terdekat' => $bolehPenggajian ? $this->jadwalTerdekat() : [],
            'komposisi_periode' => $bolehPenggajian ? $this->komposisiPerPeriode() : [],
        ]);
    }

    /**
     * Komposisi penggajian per bulan: berapa yang diterima pekerja, berapa yang
     * menjadi BPJS, dan berapa yang kembali sebagai potongan cashbon.
     *
     * Dijumlahkan di sisi basis data dan dikelompokkan per periode, bukan dimuat
     * ke memori lalu dijumlahkan di PHP.
     *
     * @return array<int, array<string, mixed>>
     */
    private function komposisiPerPeriode(): array
    {
        return PenggajianDetail::query()
            ->join('penggajians', 'penggajian_details.penggajian_id', '=', 'penggajians.id')
            ->groupBy('penggajians.periode')
            // Dibatasi di sisi basis data dan diurutkan MENURUN lebih dahulu.
            // Sebelumnya take(12) dijalankan pada koleksi hasil get() yang
            // urutannya menaik, sehingga grafiknya membeku pada dua belas
            // periode terlama dan seluruh baris dimuat ke memori tiap kali
            // dashboard dibuka.
            ->orderByDesc('penggajians.periode')
            ->limit(12)
            ->get([
                DB::raw('penggajians.periode as periode'),
                DB::raw('SUM(penggajian_details.total_gaji) as diterima'),
                DB::raw('SUM(penggajian_details.bpjs) as bpjs'),
                DB::raw('SUM(penggajian_details.potongan_cashbon) as cashbon'),
            ])
            ->reverse()
            ->map(fn ($baris): array => [
                'periode' => $baris->periode,
                'diterima' => (float) $baris->diterima,
                'bpjs' => (float) $baris->bpjs,
                'cashbon' => (float) $baris->cashbon,
            ])
            ->values()
            ->all();
    }

    /**
     * Margin per kontrak, bukan satu angka gabungan.
     *
     * Gaji tersusun sengaja dihitung hanya dari periode yang sudah diproses —
     * bukan proyeksi seluruh kontrak — sehingga labelnya di antarmuka harus
     * menyebutnya demikian agar tidak menyesatkan di awal kontrak.
     *
     * @return array<int, array<string, mixed>>
     */
    private function marginPerKontrak(): array
    {
        return Kontrak::with('client')
            ->withSum('penggajians', 'total_gaji')
            ->orderByDesc('total_biaya')
            ->take(6)
            ->get()
            ->map(fn (Kontrak $kontrak): array => [
                'id' => $kontrak->id,
                'judul' => $kontrak->judul,
                'client' => $kontrak->client?->nama_client ?? '—',
                'status' => $kontrak->status,
                'total_biaya' => (float) $kontrak->total_biaya,
                'gaji_tersusun' => (float) ($kontrak->penggajians_sum_total_gaji ?? 0),
                'tanggal_mulai' => $kontrak->tanggal_mulai?->format('Y-m-d'),
            ])
            ->all();
    }

    /**
     * Periode gajian terdekat lintas kontrak — yang sudah dibuat maupun yang
     * baru terjadwal — supaya admin tahu apa yang jatuh tempo tanpa membuka
     * kontraknya satu per satu.
     *
     * @return array<int, array<string, mixed>>
     */
    private function jadwalTerdekat(): array
    {
        $kontraks = Kontrak::with('client')
            ->whereIn('status', [StatusKontrak::Progres, StatusKontrak::Selesai])
            ->get();

        $baris = [];

        foreach ($kontraks as $kontrak) {
            try {
                $periodes = $this->jadwal->untuk($kontrak);
            } catch (Throwable) {
                // Kontrak dengan tanggal tidak valid tidak boleh menggagalkan dashboard
                continue;
            }

            $sudahAda = Penggajian::where('kontrak_id', $kontrak->id)
                ->pluck('status', 'periode');

            foreach ($periodes as $periode) {
                $status = $sudahAda[$periode->periode] ?? null;

                if ($status === StatusPenggajian::Dibayar) {
                    continue;
                }

                $baris[] = [
                    'kontrak_id' => $kontrak->id,
                    'kontrak' => $kontrak->judul,
                    'client' => $kontrak->client?->nama_client ?? '—',
                    'periode' => $periode->periode,
                    'mulai' => $periode->mulai->format('Y-m-d'),
                    'selesai' => $periode->selesai->format('Y-m-d'),
                    'tanggal_bayar' => $periode->tanggalBayar->format('Y-m-d'),
                    'dibuat' => $status !== null,
                    'jatuh_tempo' => $periode->jatuhTempo(),
                    'final' => $periode->final,
                ];
            }
        }

        usort($baris, fn (array $a, array $b): int => $a['tanggal_bayar'] <=> $b['tanggal_bayar']);

        return array_slice($baris, 0, 5);
    }

    /**
     * Sisa hutang dihitung dari yang belum terbayar, bukan sekadar cacah
     * pinjaman — itulah angka yang benar-benar menggantung.
     *
     * @return array<string, mixed>
     */
    private function ringkasanCashbon(): array
    {
        // Relasi potongans ikut dimuat: terbayar()/sisaHutang() dipanggil tiga
        // kali per baris, dan tanpa ini tiap panggilan menembak kueri sendiri.
        $cashbons = Cashbon::with(['karyawan', 'potongans.penggajianDetail.penggajian'])->get();

        $berjalan = $cashbons
            ->filter(fn (Cashbon $cashbon): bool => $cashbon->sisaHutang() >= 1)
            ->sortByDesc(fn (Cashbon $cashbon): float => $cashbon->sisaHutang())
            ->take(3)
            ->map(fn (Cashbon $cashbon): array => [
                'id' => $cashbon->id,
                'karyawan' => $cashbon->karyawan?->nama ?? '—',
                'keterangan' => $cashbon->keterangan,
                'jumlah' => (float) $cashbon->jumlah,
                'terbayar' => $cashbon->terbayar(),
                'terpotong' => $cashbon->terpotong(),
                'sisa' => $cashbon->sisaHutang(),
            ])
            ->values()
            ->all();

        return [
            'total' => (float) $cashbons->sum(fn (Cashbon $cashbon): float => (float) $cashbon->jumlah),
            'terbayar' => $cashbons->sum(fn (Cashbon $cashbon): float => $cashbon->terbayar()),
            'sisa' => $cashbons->sum(fn (Cashbon $cashbon): float => $cashbon->sisaHutang()),
            // Satu definisi "berjalan" untuk seluruh payload: diturunkan dari
            // sisa hutangnya, bukan dari kolom status. Dahulu angka di kartu
            // memakai kolom status sementara daftar di bawahnya memakai sisa
            // hutang — dua angka bersebelahan dengan dua arti berbeda.
            'pending_count' => $cashbons->filter(fn (Cashbon $cashbon): bool => $cashbon->sisaHutang() >= 1)->count(),
            'berjalan' => $berjalan,
        ];
    }

    /**
     * Bentuk yang sama, tanpa angka — untuk pengguna tanpa izin cashbon.
     *
     * @return array<string, mixed>
     */
    private function ringkasanCashbonKosong(): array
    {
        return [
            'total' => 0.0,
            'terbayar' => 0.0,
            'sisa' => 0.0,
            'pending_count' => 0,
            'berjalan' => [],
        ];
    }
}
