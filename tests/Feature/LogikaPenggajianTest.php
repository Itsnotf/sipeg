<?php

use App\Enums\StatusCashbon;
use App\Enums\StatusKontrak;
use App\Exceptions\KesalahanAturan;
use App\Models\Cashbon;
use App\Models\CashbonPotongan;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\Kontrak;
use App\Models\KontrakKaryawan;
use App\Models\Penggajian;
use App\Services\PenggajianService;
use Carbon\Carbon;

/**
 * Cacat logika pembagian uang antar periode.
 *
 * Berkas ini dibuat setelah audit menemukan bahwa 255 tes hijau tidak
 * membuktikan uangnya benar: totalnya selalu menjumlah, sehingga tes "slip
 * selalu pas" tetap lolos — yang salah adalah pembagian cicilan antar periode
 * dan kepada siapa hutang dibebankan.
 *
 * Angka yang dipakai seluruh berkas ini:
 *   gaji pokok sebulan  10.000.000
 *   BPJS 5%                500.000
 *   gaji bersih          9.500.000
 *   plafon potongan 50%  4.750.000 per periode
 */
beforeEach(function () {
    $this->jabatan = Jabatan::factory()->gaji(10_000_000, 5.0)->create();
    $this->service = app(PenggajianService::class);
});

afterEach(function () {
    Carbon::setTestNow();
});

/** Kontrak berjalan dengan satu pekerja yang ditempatkan sejak hari pertama. */
function siapkanKontrak(Jabatan $jabatan, string $mulai = '2026-01-01', string $selesai = '2026-06-30'): array
{
    $karyawan = Karyawan::factory()->aktif()->create(['id_jabatan' => $jabatan->id]);
    $kontrak = Kontrak::factory()->periode($mulai, $selesai, 25)->create();

    KontrakKaryawan::factory()->create([
        'kontrak_id' => $kontrak->id,
        'karyawan_id' => $karyawan->id,
        'tanggal_mulai' => $mulai,
    ]);

    return [$kontrak, $karyawan];
}

/** Potongan cashbon per periode, dipetakan periode => nominal. */
function potonganPerPeriode(Kontrak $kontrak): array
{
    return Penggajian::where('kontrak_id', $kontrak->id)
        ->with('penggajianDetails')
        ->orderBy('periode')
        ->get()
        ->mapWithKeys(fn (Penggajian $p): array => [
            $p->periode => (float) $p->penggajianDetails->sum('potongan_cashbon'),
        ])
        ->all();
}

it('A1 — mencicil dari periode terlama lebih dahulu setelah nominal cashbon dikoreksi turun', function () {
    [$kontrak, $karyawan] = siapkanKontrak($this->jabatan);

    Carbon::setTestNow('2026-01-05');
    $cashbon = Cashbon::factory()->jumlah(12_000_000)->create(['karyawan_id' => $karyawan->id]);

    // Jan dibayar 25 Feb, Feb dibayar 25 Mar, Mar dibayar 25 Apr.
    Carbon::setTestNow('2026-04-25');
    $this->service->proses($kontrak);

    expect(potonganPerPeriode($kontrak))->toBe([
        '2026-01-01' => 4_750_000.0,
        '2026-02-01' => 4_750_000.0,
        '2026-03-01' => 2_500_000.0,
    ]);

    // Koreksi turun menjadi 6 jt: cicilannya harus menyusut dari periode
    // TERBARU, bukan dari yang terlama.
    $cashbon->update(['jumlah' => 6_000_000]);
    $this->service->hitungUlangUntukKaryawan($karyawan->id);

    expect(potonganPerPeriode($kontrak))->toBe([
        '2026-01-01' => 4_750_000.0,
        '2026-02-01' => 1_250_000.0,
        '2026-03-01' => 0.0,
    ]);
});

it('A2 — melunasi sisa hutang pada slip terakhir pekerja, bukan hanya pada periode terakhir kontrak', function () {
    [$kontrak, $karyawan] = siapkanKontrak($this->jabatan);

    Carbon::setTestNow('2026-01-05');
    $cashbon = Cashbon::factory()->jumlah(15_500_000)->create(['karyawan_id' => $karyawan->id]);

    // Jan + Feb terpotong penuh plafon: 4,75 jt + 4,75 jt, sisa 6 jt.
    Carbon::setTestNow('2026-03-25');
    $this->service->proses($kontrak);

    // Pekerja dilepas 26 Maret — Maret menjadi slip terakhirnya, padahal
    // kontraknya sendiri masih berjalan sampai Juni.
    Carbon::setTestNow('2026-03-26');
    KontrakKaryawan::where('karyawan_id', $karyawan->id)
        ->update(['tanggal_selesai' => '2026-03-26']);

    Carbon::setTestNow('2026-04-25');
    $this->service->proses($kontrak->fresh());

    $maret = Penggajian::where('kontrak_id', $kontrak->id)->where('periode', '2026-03-01')->firstOrFail();

    // 26 hari dari 31: gaji 8.387.097, BPJS 419.355, bersih 7.967.742.
    // Plafon 50% hanya 3.983.871 — tidak cukup untuk sisa 6 jt.
    expect((float) $maret->penggajianDetails()->sum('potongan_cashbon'))->toBe(6_000_000.0)
        ->and($cashbon->fresh()->sisa())->toBe(0.0);

    $this->service->tandaiDibayar(
        Penggajian::where('kontrak_id', $kontrak->id)->where('periode', '2026-01-01')->firstOrFail()
    );
    $this->service->tandaiDibayar(
        Penggajian::where('kontrak_id', $kontrak->id)->where('periode', '2026-02-01')->firstOrFail()
    );
    $this->service->tandaiDibayar($maret);

    expect($cashbon->fresh()->status)->toBe(StatusCashbon::Lunas);
});

it('A3 — tetap menagih lewat slip yang belum dibayar sekalipun pinjamannya diambil belakangan', function () {
    [$kontrak, $karyawan] = siapkanKontrak($this->jabatan, '2026-01-01', '2026-12-31');

    Carbon::setTestNow('2026-03-25');
    $this->service->proses($kontrak);

    /*
    | Keputusan yang sengaja dikunci di sini.
    |
    | Sempat dipertimbangkan menolak pinjaman yang diambil setelah bulan
    | kerjanya lewat, supaya slip Januari tidak memuat potongan untuk hutang
    | bulan Juni. Aturan itu ditolak: slip yang belum dibayar adalah tagihan
    | yang masih terbuka. Bila disaring, perusahaan yang telat menggaji tidak
    | punya slip tersisa untuk menagih dan hutangnya tidak pernah tertagih.
    */
    Carbon::setTestNow('2026-06-10');
    Cashbon::factory()->jumlah(5_000_000)->create(['karyawan_id' => $karyawan->id]);
    $this->service->hitungUlangUntukKaryawan($karyawan->id);

    expect(potonganPerPeriode($kontrak))->toBe([
        '2026-01-01' => 4_750_000.0,
        '2026-02-01' => 250_000.0,
    ]);
});

it('A4 — memakai pembagi prorata yang dibekukan, bukan yang dihitung ulang dari config', function () {
    [$kontrak, $karyawan] = siapkanKontrak($this->jabatan);

    Carbon::setTestNow('2026-02-25');
    $this->service->proses($kontrak);

    $januari = Penggajian::where('kontrak_id', $kontrak->id)->where('periode', '2026-01-01')->firstOrFail();

    expect((float) $januari->penggajianDetails()->first()->gaji_pokok)->toBe(10_000_000.0);

    // Kebijakan pembagi diubah menjadi 30 hari tetap. Slip yang SUDAH tersusun
    // tidak boleh ikut berubah — hari_periode-nya sudah dibekukan di 31.
    config()->set('payroll.hari_per_bulan_kalender', false);

    app(PenggajianService::class)->hitungUlang($januari);

    expect((float) $januari->fresh()->penggajianDetails()->first()->gaji_pokok)->toBe(10_000_000.0);
});

it('A5 — tidak pernah memotong lebih besar dari hutangnya sendiri', function () {
    [$kontrak, $karyawan] = siapkanKontrak($this->jabatan);

    Carbon::setTestNow('2026-01-05');
    $cashbon = Cashbon::factory()->create(['karyawan_id' => $karyawan->id, 'jumlah' => 1_000_000.60]);

    Carbon::setTestNow('2026-02-25');
    $this->service->proses($kontrak);

    $terpotong = (float) CashbonPotongan::where('cashbon_id', $cashbon->id)->sum('jumlah');

    expect($terpotong)->toBeLessThanOrEqual(1_000_000.60);
});

it('C1 — menolak memproses penggajian kontrak yang belum berjalan', function () {
    [$kontrak, $karyawan] = siapkanKontrak($this->jabatan);
    $kontrak->update(['status' => StatusKontrak::Pending]);

    Carbon::setTestNow('2026-02-25');

    expect(fn () => $this->service->proses($kontrak->fresh()))
        ->toThrow(KesalahanAturan::class);

    expect(Penggajian::where('kontrak_id', $kontrak->id)->count())->toBe(0);
});

it('C2 — menolak membayar periode saat masih ada periode lebih lama yang belum dibayar', function () {
    [$kontrak, $karyawan] = siapkanKontrak($this->jabatan);

    Carbon::setTestNow('2026-03-25');
    $this->service->proses($kontrak);

    $februari = Penggajian::where('kontrak_id', $kontrak->id)->where('periode', '2026-02-01')->firstOrFail();

    expect(fn () => $this->service->tandaiDibayar($februari))
        ->toThrow(KesalahanAturan::class);
});
