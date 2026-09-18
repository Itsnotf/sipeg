<?php

namespace App\Services;

use App\Enums\StatusCashbon;
use App\Enums\StatusPenggajian;
use App\Exceptions\KesalahanAturan;
use App\Exceptions\PenggajianTerkunci;
use App\Models\Cashbon;
use App\Models\CashbonPotongan;
use App\Models\Kontrak;
use App\Models\KontrakKaryawan;
use App\Models\Penggajian;
use App\Models\PenggajianDetail;
use App\Support\JadwalGajian;
use App\Support\PeriodeGajian;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Mesin penggajian.
 *
 * Tiga sifat yang dijaga kelas ini:
 *
 * 1. Idempoten — memproses periode yang sama berkali-kali tidak mengubah apa
 *    pun, dan menghitung ulang dari bahan yang sama menghasilkan angka yang
 *    sama persis.
 * 2. Terlacak — setiap rupiah potongan cashbon punya baris buku besarnya
 *    sendiri, sehingga tidak perlu ditebak saat dilepas.
 * 3. Tegas — kegagalan dilempar sebagai exception, tidak ditelan menjadi
 *    baris log seperti observer yang digantikannya.
 */
class PenggajianService
{
    public function __construct(private JadwalGajian $jadwal) {}

    /**
     * Membuat penggajian untuk setiap periode yang tanggal bayarnya sudah tiba
     * dan belum pernah dibuat.
     *
     * @return array<int, Penggajian>
     */
    public function proses(Kontrak $kontrak, ?CarbonInterface $pada = null): array
    {
        // Tombol "Proses" di layar dahulu tidak memeriksa status sama sekali,
        // sementara perintah terjadwal menyaring Progres + Selesai. Kontrak yang
        // belum berjalan pun bisa menghasilkan penggajian yang langsung terkunci.
        if (! $kontrak->status->bolehDiproses()) {
            throw new KesalahanAturan(
                'Penggajian hanya dapat diproses untuk kontrak yang sudah berjalan.'
            );
        }

        $periodes = $this->jadwal->jatuhTempo($kontrak, $pada);

        if ($periodes === []) {
            return [];
        }

        // load(), bukan loadMissing(): pemanggil bisa mengirim model yang
        // relasinya sudah dimuat sebelum penempatan berubah, dan penggajian
        // akan tersusun dari daftar pekerja yang basi.
        $kontrak->load('kontrakKaryawans.karyawan.jabatan');

        return DB::transaction(function () use ($kontrak, $periodes): array {
            // Dikunci: dua klik "Proses" yang tiba bersamaan sama-sama lolos
            // saringan di bawah, lalu yang kedua menabrak batasan unik
            // (kontrak_id, periode) dan me-rollback seluruh batch.
            $sudahAda = Penggajian::where('kontrak_id', $kontrak->id)
                ->lockForUpdate()
                ->pluck('periode')
                ->all();

            $dibuat = [];

            foreach ($periodes as $periode) {
                if (in_array($periode->periode, $sudahAda, true)) {
                    continue;
                }

                $dibuat[] = $this->materialisasi($kontrak, $periode);
            }

            return $dibuat;
        });
    }

    /**
     * Menghitung ulang penggajian yang belum dibayar dari bahan yang sudah
     * dibekukan, lalu mengalokasikan ulang cashbon dari nol.
     */
    public function hitungUlang(Penggajian $penggajian): Penggajian
    {
        if ($penggajian->terkunci()) {
            throw PenggajianTerkunci::untukPeriode((string) $penggajian->periode);
        }

        $kontrak = $penggajian->kontrak()->with('kontrakKaryawans.karyawan.jabatan')->firstOrFail();
        $periode = $this->jadwal->cari($kontrak, (string) $penggajian->periode);

        if (! $periode instanceof PeriodeGajian) {
            throw new KesalahanAturan(
                "Periode {$penggajian->periode} tidak lagi berada dalam jadwal kontrak ini."
            );
        }

        DB::transaction(function () use ($penggajian, $kontrak, $periode): void {
            $this->susunDetail($penggajian, $kontrak, $periode, bekukanUlang: false);
        });

        return $penggajian->refresh();
    }

    /**
     * Menghitung ulang seluruh penggajian belum dibayar milik seorang pekerja.
     * Dipakai setelah cashbon berubah.
     */
    public function hitungUlangUntukKaryawan(int $karyawanId): void
    {
        // Ditelusuri lewat kontrak, bukan lewat baris detail yang sudah ada,
        // agar penggajian yang belum memuat pekerja ini pun ikut tersusun ulang.
        $kontrakIds = KontrakKaryawan::where('karyawan_id', $karyawanId)->pluck('kontrak_id');

        $penggajians = Penggajian::belumDibayar()
            ->whereIn('kontrak_id', $kontrakIds)
            ->orderBy('periode')
            ->get();

        DB::transaction(function () use ($karyawanId, $penggajians): void {
            /*
            | Dua fase, dan urutannya menentukan benar-tidaknya cicilan.
            |
            | susunDetail hanya melepas buku besar milik penggajian yang sedang
            | disusun, sedangkan sisa hutang dibaca dari SELURUH buku besar.
            | Menghitung ulang satu per satu karena itu membuat periode terlama
            | melihat jatahnya seolah sudah terpakai oleh periode yang lebih
            | baru: cashbon 12 jt yang dikoreksi menjadi 6 jt menghasilkan
            | Januari Rp 0, Februari 3,5 jt, Maret 2,5 jt — padahal cicilan
            | seharusnya menyusut dari periode TERBARU, bukan dari yang terlama.
            |
            | Maka seluruh alokasi belum dibayar dilepas lebih dahulu, baru
            | periodenya disusun ulang menaik.
            */
            $this->lepasAlokasiBelumDibayar($karyawanId, $penggajians->pluck('id')->all());

            foreach ($penggajians as $penggajian) {
                $this->hitungUlang($penggajian);
            }
        });
    }

    /**
     * Melepas seluruh pemesanan cashbon seorang pekerja pada penggajian yang
     * belum dibayar, sehingga alokasi ulang berangkat dari papan yang bersih.
     *
     * @param  array<int, int>  $penggajianIds
     */
    private function lepasAlokasiBelumDibayar(int $karyawanId, array $penggajianIds): void
    {
        if ($penggajianIds === []) {
            return;
        }

        $detailIds = PenggajianDetail::whereIn('penggajian_id', $penggajianIds)
            ->where('karyawan_id', $karyawanId)
            ->pluck('id');

        CashbonPotongan::whereIn('penggajian_detail_id', $detailIds)->delete();
    }

    /**
     * Mengunci penggajian sebagai sudah dibayar dan menyegarkan status cashbon
     * yang ikut terpotong di dalamnya.
     */
    public function tandaiDibayar(Penggajian $penggajian): Penggajian
    {
        if ($penggajian->terkunci()) {
            throw PenggajianTerkunci::untukPeriode((string) $penggajian->periode);
        }

        /*
        | Periode dibayar menurut urutannya.
        |
        | Membayar Juni sebelum Januari mengunci alokasi cashbon Juni secara
        | permanen — dan alokasi itu dihitung dengan anggapan Januari belum
        | mengambil jatahnya. Januari tidak akan pernah bisa memulihkan
        | cicilannya yang benar, bahkan setelah dihitung ulang.
        */
        $tertinggal = Penggajian::belumDibayar()
            ->where('kontrak_id', $penggajian->kontrak_id)
            ->where('periode', '<', $penggajian->periode)
            ->orderBy('periode')
            ->first();

        if ($tertinggal instanceof Penggajian) {
            throw new KesalahanAturan(sprintf(
                'Periode %s harus dibayar lebih dahulu sebelum periode %s.',
                $tertinggal->periode,
                $penggajian->periode
            ));
        }

        DB::transaction(function () use ($penggajian): void {
            $penggajian->update(['status' => StatusPenggajian::Dibayar]);

            $cashbonIds = CashbonPotongan::whereIn(
                'penggajian_detail_id',
                $penggajian->penggajianDetails()->pluck('id')
            )->pluck('cashbon_id')->unique();

            foreach (Cashbon::whereIn('id', $cashbonIds)->get() as $cashbon) {
                $this->segarkanStatusCashbon($cashbon);
            }
        });

        return $penggajian->refresh();
    }

    /**
     * Status cashbon diturunkan dari buku besar, tidak pernah ditetapkan manual.
     */
    public function segarkanStatusCashbon(Cashbon $cashbon): void
    {
        // Lunas diukur dari yang sudah benar-benar dipotong pada penggajian
        // terbayar, bukan dari yang sekadar dialokasikan. Alokasi pada
        // penggajian yang belum dibayar masih bisa dilepas kembali.
        //
        // Sisa di bawah satu rupiah dianggap habis — rupiah tidak punya pecahan,
        // dan perbandingan persis dengan nol pada bilangan pecahan tidak aman.
        $status = $cashbon->fresh()->sisaHutang() < 1
            ? StatusCashbon::Lunas
            : StatusCashbon::Berjalan;

        if ($cashbon->status !== $status) {
            $cashbon->update(['status' => $status]);
        }
    }

    private function materialisasi(Kontrak $kontrak, PeriodeGajian $periode): Penggajian
    {
        $penggajian = Penggajian::create([
            'kontrak_id' => $kontrak->id,
            'periode' => $periode->periode,
            'periode_mulai' => $periode->mulai->format('Y-m-d'),
            'periode_selesai' => $periode->selesai->format('Y-m-d'),
            'tanggal_bayar' => $periode->tanggalBayar->format('Y-m-d'),
            'final' => $periode->final,
            'status' => StatusPenggajian::BelumDibayar,
            'total_gaji' => 0,
        ]);

        $this->susunDetail($penggajian, $kontrak, $periode, bekukanUlang: true);

        return $penggajian->refresh();
    }

    /**
     * Menyusun ulang seluruh baris detail sebuah penggajian.
     *
     * Alokasi cashbon dilepas seluruhnya lebih dahulu, baru dialokasikan ulang.
     * Memperbarui alokasi lama di tempat akan meninggalkan baris basi ketika
     * sebuah cashbon berpindah urutan atau lunas di periode sebelumnya.
     */
    private function susunDetail(
        Penggajian $penggajian,
        Kontrak $kontrak,
        PeriodeGajian $periode,
        bool $bekukanUlang,
    ): void {
        $detailLama = $penggajian->penggajianDetails()->get()->keyBy('karyawan_id');

        CashbonPotongan::whereIn('penggajian_detail_id', $detailLama->pluck('id'))->delete();

        $bpjsDefault = (float) config('payroll.bpjs_persen_default', 5.0);
        $karyawanTersusun = [];

        foreach ($kontrak->kontrakKaryawans as $penempatan) {
            $hariAktif = $periode->hariAktif($penempatan->tanggal_mulai, $penempatan->tanggal_selesai);

            // Pekerja yang penempatannya tidak menyentuh periode ini tidak
            // mendapat baris sama sekali — baris bernilai nol hanya akan
            // menggelembungkan jumlah pekerja pada ringkasan.
            if ($hariAktif <= 0) {
                continue;
            }

            $detail = $detailLama->get($penempatan->karyawan_id);
            $jabatan = $penempatan->karyawan?->jabatan;

            // Tanpa jabatan tidak ada gaji yang bisa dihitung. Baris bernilai
            // nol hanya menggelembungkan jumlah pekerja pada ringkasan —
            // persis yang dihindari oleh saringan hariAktif di atas.
            if ($jabatan === null) {
                continue;
            }

            $gajiPenuh = ($bekukanUlang || ! $detail)
                ? (float) $jabatan->gaji
                : (float) $detail->gaji_pokok_penuh;

            $bpjsPersen = ($bekukanUlang || ! $detail)
                ? (float) ($jabatan->bpjs_persen ?? $bpjsDefault)
                : (float) $detail->bpjs_persen;

            // Pembagi prorata ikut dibekukan, sama seperti gaji dan persentase
            // BPJS. Tanpa ini, membalik payroll.hari_per_bulan_kalender akan
            // menghitung ulang slip lama dengan pembagi 30 hari — pekerja yang
            // hadir penuh pada bulan 31 hari menerima 103,3% gaji sebulan.
            $hariPeriode = ($bekukanUlang || ! $detail)
                ? $periode->hariPeriode
                : (int) $detail->hari_periode;

            $gajiPokok = $this->bulatkan(
                $hariPeriode > 0 ? $gajiPenuh * $hariAktif / $hariPeriode : 0.0
            );
            $bpjs = $this->bulatkan($gajiPokok * $bpjsPersen / 100);

            if ($gajiPokok - $bpjs < 0) {
                throw new KesalahanAturan(
                    "Potongan BPJS jabatan melebihi gaji pokok pada periode {$periode->periode}."
                );
            }

            $detail = PenggajianDetail::updateOrCreate(
                [
                    'penggajian_id' => $penggajian->id,
                    'karyawan_id' => $penempatan->karyawan_id,
                ],
                [
                    'gaji_pokok_penuh' => $gajiPenuh,
                    'gaji_pokok' => $gajiPokok,
                    'bpjs_persen' => $bpjsPersen,
                    'bpjs' => $bpjs,
                    'hari_aktif' => $hariAktif,
                    'hari_periode' => $hariPeriode,
                    'potongan_cashbon' => 0,
                    'total_gaji' => $gajiPokok - $bpjs,
                ]
            );

            /*
            | Plafon potongan diabaikan pada slip TERAKHIR pekerja ini, bukan
            | hanya pada periode terakhir kontrak.
            |
            | Pekerja yang dilepas di tengah kontrak tidak mendapat baris lagi
            | pada periode berikutnya, sehingga sisa hutangnya menggantung
            | selamanya dan plafon pinjamannya terkunci — persis yang hendak
            | dicegah oleh aturan "lunasi di periode terakhir".
            */
            $slipTerakhir = $periode->final || (
                $penempatan->tanggal_selesai !== null
                && CarbonImmutable::parse($penempatan->tanggal_selesai)
                    ->startOfDay()
                    ->lessThanOrEqualTo($periode->selesai)
            );

            $this->alokasikanCashbon($detail, $periode, $slipTerakhir);
            $karyawanTersusun[] = $penempatan->karyawan_id;
        }

        // Baris milik pekerja yang sudah tidak lagi berada di periode ini
        $penggajian->penggajianDetails()
            ->whereNotIn('karyawan_id', $karyawanTersusun ?: [0])
            ->delete();

        $penggajian->update([
            'total_gaji' => (float) $penggajian->penggajianDetails()->sum('total_gaji'),
        ]);
    }

    /**
     * Memotong cashbon secara berurutan dari yang paling lama, sebatas plafon
     * periode ini. Sisanya terbawa ke periode berikutnya sebagai cicilan.
     *
     * Pada slip terakhir seorang pekerja plafon diabaikan, agar hutangnya tidak
     * menggantung tanpa slip berikutnya untuk melunasinya.
     */
    private function alokasikanCashbon(
        PenggajianDetail $detail,
        PeriodeGajian $periode,
        bool $slipTerakhir,
    ): void {
        $gajiBersih = (float) $detail->gaji_pokok - (float) $detail->bpjs;

        $sisaPlafon = $slipTerakhir
            ? $gajiBersih
            : $this->bulatkan($gajiBersih * (float) config('payroll.batas_potongan', 0.5));

        if ($sisaPlafon <= 0) {
            return;
        }

        /*
        | Seluruh cashbon pekerja ini ikut diperhitungkan, termasuk yang diambil
        | setelah bulan kerjanya berakhir.
        |
        | Sempat dicoba menyaringnya berdasarkan tanggal pinjaman, dengan alasan
        | slip Januari tidak sepatutnya memuat potongan untuk hutang bulan Juni.
        | Itu keliru: slip yang belum dibayar adalah tagihan yang masih terbuka,
        | bukan catatan sejarah. Saat perusahaan akhirnya membayar Januari,
        | uangnya berpindah pada hari itu — dan hutang yang menggantung memang
        | sepatutnya diperhitungkan di situ. Dengan saringan tadi, perusahaan
        | yang telat menggaji justru kehilangan satu-satunya kesempatan menagih.
        */
        $cashbons = Cashbon::where('karyawan_id', $detail->karyawan_id)
            ->withSum('potongans', 'jumlah')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $totalPotongan = 0.0;

        foreach ($cashbons as $cashbon) {
            if ($sisaPlafon < 1) {
                break;
            }

            $sisaHutang = $cashbon->sisa();

            if ($sisaHutang < 1) {
                continue;
            }

            // Dibulatkan ke BAWAH: round() pada hutang bernilai pecahan bisa
            // menarik sampai Rp 0,49 lebih besar dari hutangnya sendiri, dan
            // kelebihannya tersembunyi karena sisaHutang() diklem di nol.
            $ambil = floor(min($sisaPlafon, $sisaHutang));

            if ($ambil < 1) {
                continue;
            }

            CashbonPotongan::create([
                'cashbon_id' => $cashbon->id,
                'penggajian_detail_id' => $detail->id,
                'jumlah' => $ambil,
            ]);

            $sisaPlafon -= $ambil;
            $totalPotongan += $ambil;

            $this->segarkanStatusCashbon($cashbon);
        }

        $detail->update([
            'potongan_cashbon' => $totalPotongan,
            'total_gaji' => $gajiBersih - $totalPotongan,
        ]);
    }

    /**
     * Rupiah tidak punya satuan di bawah 1. Setiap komponen dibulatkan sebelum
     * dipakai menghitung komponen berikutnya, sehingga slip selalu menjumlah
     * tepat dan tidak meninggalkan selisih sen.
     */
    private function bulatkan(float $nilai): float
    {
        return round($nilai, (int) config('payroll.pembulatan', 0));
    }
}
