<?php

namespace App\Services;

use App\Enums\StatusCashbon;
use App\Enums\StatusPenggajian;
use App\Exceptions\KesalahanAturan;
use App\Models\Cashbon;
use App\Models\CashbonPotongan;
use App\Models\Karyawan;
use Illuminate\Support\Facades\DB;

/**
 * Pintu masuk tunggal untuk perubahan cashbon.
 *
 * Setiap perubahan langsung menyusun ulang penggajian yang belum dibayar milik
 * pekerja bersangkutan, di dalam satu transaksi, dan kegagalannya dilempar ke
 * pemanggil. Inilah pengganti CashbonObserver, yang dahulu menelan kegagalan
 * menjadi baris log sehingga potongan bisa gagal tanpa ada yang tahu.
 */
class CashbonService
{
    public function __construct(private PenggajianService $penggajian) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function buat(array $data): Cashbon
    {
        $karyawan = Karyawan::with('jabatan')->findOrFail($data['karyawan_id']);
        $jumlah = (float) $data['jumlah'];

        return DB::transaction(function () use ($data, $jumlah, $karyawan): Cashbon {
            // Diperiksa DI DALAM transaksi dan dengan kunci baris. Sebelumnya
            // pemeriksaan berada di luar tanpa kunci, sehingga dua pengajuan
            // yang tiba bersamaan sama-sama lolos dan plafonnya tertembus.
            $this->pastikanTidakMelebihiPlafon($karyawan, $jumlah);

            $cashbon = Cashbon::create([
                'karyawan_id' => $karyawan->id,
                'jumlah' => $jumlah,
                'keterangan' => $data['keterangan'],
                'status' => StatusCashbon::Berjalan,
            ]);

            $this->penggajian->hitungUlangUntukKaryawan($karyawan->id);

            return $cashbon->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function ubah(Cashbon $cashbon, array $data): Cashbon
    {
        $this->pastikanMasihDalamJendelaKoreksi($cashbon);

        $jumlahBaru = (float) $data['jumlah'];
        $terbayar = $this->jumlahTerbayar($cashbon);

        // Nominal tidak boleh turun di bawah yang sudah benar-benar dipotong
        // dari gaji yang sudah dibayarkan — uangnya sudah berpindah.
        if ($jumlahBaru < $terbayar) {
            throw new KesalahanAturan(sprintf(
                'Jumlah cashbon tidak boleh kurang dari Rp %s yang sudah dipotong pada penggajian terbayar.',
                number_format($terbayar, 0, ',', '.')
            ));
        }

        $karyawanLamaId = (int) $cashbon->karyawan_id;
        $karyawanBaruId = (int) ($data['karyawan_id'] ?? $karyawanLamaId);
        $pindah = $karyawanBaruId !== $karyawanLamaId;

        /*
        | Peminjam boleh dikoreksi selama uangnya belum berpindah.
        |
        | Formulir edit selalu menampilkan dropdown peminjam dan permintaannya
        | memvalidasi karyawan_id, tetapi nilainya dahulu dibuang tanpa jejak:
        | mengoreksi cashbon yang salah orang tampak berhasil padahal hutangnya
        | tetap menempel pada orang yang salah.
        */
        if ($pindah && $terbayar > 0) {
            throw new KesalahanAturan(
                'Cashbon ini sudah dipotong dari gaji yang telah dibayarkan, sehingga peminjamnya tidak dapat dipindahkan.'
            );
        }

        return DB::transaction(function () use ($cashbon, $data, $jumlahBaru, $karyawanLamaId, $karyawanBaruId, $pindah): Cashbon {
            $karyawan = Karyawan::with('jabatan')->findOrFail($karyawanBaruId);

            $this->pastikanTidakMelebihiPlafon($karyawan, $jumlahBaru, $cashbon);

            $cashbon->update([
                'karyawan_id' => $karyawan->id,
                'jumlah' => $jumlahBaru,
                'keterangan' => $data['keterangan'],
            ]);

            if ($pindah) {
                // Pemesanan lama menempel pada slip pekerja sebelumnya.
                $cashbon->potongans()->delete();
                $this->penggajian->hitungUlangUntukKaryawan($karyawanLamaId);
            }

            $this->penggajian->hitungUlangUntukKaryawan($karyawanBaruId);
            $this->penggajian->segarkanStatusCashbon($cashbon);

            return $cashbon->refresh();
        });
    }

    public function hapus(Cashbon $cashbon): void
    {
        $this->pastikanMasihDalamJendelaKoreksi($cashbon);

        if ($this->jumlahTerbayar($cashbon) > 0) {
            throw new KesalahanAturan(
                'Cashbon ini sudah dipotong pada penggajian yang telah dibayar dan tidak dapat dihapus.'
            );
        }

        DB::transaction(function () use ($cashbon): void {
            $karyawanId = $cashbon->karyawan_id;

            // Buku besar dijaga restrictOnDelete, jadi alokasinya harus dilepas
            // lebih dahulu. Semuanya berada pada penggajian yang belum dibayar,
            // sebagaimana sudah dipastikan di atas.
            $cashbon->potongans()->delete();
            $cashbon->delete();

            $this->penggajian->hitungUlangUntukKaryawan($karyawanId);
        });
    }

    /**
     * Batas total hutang berjalan seorang pekerja: sekian kali gaji bersih
     * bulanannya.
     *
     * Kebijakan ini berbeda satuan dari batas potongan per periode — yang satu
     * membatasi berapa boleh dipinjam seluruhnya, yang lain berapa boleh
     * dipotong sekali gajian. Menyamakan keduanya membuat setiap pinjaman
     * selalu lunas dalam satu periode dan cicilan tidak pernah terpakai.
     */
    public function plafonPinjaman(Karyawan $karyawan): float
    {
        return round($this->gajiBersihBulanan($karyawan) * (float) config('payroll.maks_hutang_bulan', 3));
    }

    public function gajiBersihBulanan(Karyawan $karyawan): float
    {
        $gaji = (float) ($karyawan->jabatan->gaji ?? 0);
        $persen = (float) ($karyawan->jabatan->bpjs_persen ?? config('payroll.bpjs_persen_default', 5.0));

        return $gaji - round($gaji * $persen / 100);
    }

    /**
     * Total hutang yang masih ditanggung pekerja, boleh mengecualikan satu
     * cashbon yang sedang diubah.
     *
     * Diukur dari yang belum terbayar, bukan dari yang belum dialokasikan —
     * hutang yang sudah dipesan pada penggajian yang belum dibayar tetap hutang.
     */
    public function hutangBerjalan(Karyawan $karyawan, ?int $kecualikanId = null, bool $kunci = false): float
    {
        return Cashbon::where('karyawan_id', $karyawan->id)
            ->when($kecualikanId, fn ($query) => $query->where('id', '!=', $kecualikanId))
            ->when($kunci, fn ($query) => $query->lockForUpdate())
            ->get()
            ->sum(fn (Cashbon $cashbon): float => $cashbon->sisaHutang());
    }

    private function pastikanTidakMelebihiPlafon(Karyawan $karyawan, float $jumlah, ?Cashbon $diubah = null): void
    {
        $plafon = $this->plafonPinjaman($karyawan);

        /*
        | Pinjaman yang sedang diubah dihitung dengan satuan yang sama seperti
        | pinjaman lain: sisa hutangnya, bukan nilai mukanya.
        |
        | Sebelumnya pinjaman lain dihitung neto sementara yang diubah dihitung
        | bruto, sehingga pinjaman 20 jt yang sudah dicicil 10 jt dan dinaikkan
        | menjadi 25 jt diuji sebagai 25 jt — padahal hutang sesungguhnya yang
        | akan tersisa hanya 15 jt.
        */
        $sisaYangDiubah = $diubah instanceof Cashbon
            ? max(0.0, $jumlah - $this->jumlahTerbayar($diubah))
            : $jumlah;

        $total = $this->hutangBerjalan($karyawan, $diubah?->id, kunci: true) + $sisaYangDiubah;

        if ($plafon <= 0) {
            throw new KesalahanAturan(
                'Jabatan pekerja ini belum memiliki gaji, sehingga plafon cashbon tidak dapat dihitung.'
            );
        }

        if ($total > $plafon) {
            throw new KesalahanAturan(sprintf(
                'Total cashbon berjalan Rp %s melebihi plafon Rp %s untuk pekerja ini.',
                number_format($total, 0, ',', '.'),
                number_format($plafon, 0, ',', '.')
            ));
        }
    }

    private function pastikanMasihDalamJendelaKoreksi(Cashbon $cashbon): void
    {
        $hari = (int) config('payroll.jendela_koreksi_hari', 1);

        if ($cashbon->created_at->lt(now()->subDays($hari))) {
            throw new KesalahanAturan(
                "Cashbon hanya dapat diubah atau dihapus dalam {$hari} hari sejak dibuat."
            );
        }
    }

    /**
     * Bagian cashbon yang sudah dipotong pada penggajian yang telah dibayar.
     */
    private function jumlahTerbayar(Cashbon $cashbon): float
    {
        return (float) CashbonPotongan::where('cashbon_id', $cashbon->id)
            ->whereHas(
                'penggajianDetail.penggajian',
                fn ($query) => $query->where('status', StatusPenggajian::Dibayar)
            )
            ->sum('jumlah');
    }
}
