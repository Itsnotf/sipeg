<?php

use App\Enums\StatusCashbon;
use App\Enums\StatusPenggajian;
use App\Exceptions\PenggajianTerkunci;
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
 * Seluruh berkas ini mengunci waktu. Materialisasi hanya membuat periode yang
 * sudah jatuh tempo, sehingga tanpa waktu yang dikunci suite akan lulus pada
 * tanggal tertentu dan gagal pada tanggal lain tanpa sebab yang jelas.
 */
beforeEach(function () {
    Carbon::setTestNow('2026-04-01');

    $this->jabatan = Jabatan::factory()->gaji(10_000_000, 5.0)->create();

    $this->karyawan = Karyawan::factory()->create([
        'id_jabatan' => $this->jabatan->id,
    ]);

    $this->kontrak = Kontrak::factory()
        ->periode('2026-01-01', '2026-06-30', 25)
        ->create();

    KontrakKaryawan::factory()->create([
        'kontrak_id' => $this->kontrak->id,
        'karyawan_id' => $this->karyawan->id,
        'tanggal_mulai' => '2026-01-01',
    ]);

    $this->service = app(PenggajianService::class);
});

afterEach(function () {
    Carbon::setTestNow();
});

it('hanya membuat penggajian untuk periode yang sudah jatuh tempo', function () {
    // Periode Januari dibayar 25 Februari, Februari dibayar 25 Maret.
    // Maret baru dibayar 25 April, jadi belum boleh ada pada 1 April.
    $this->service->proses($this->kontrak);

    $periodes = Penggajian::where('kontrak_id', $this->kontrak->id)
        ->orderBy('periode')->pluck('periode')->all();

    expect($periodes)->toBe(['2026-01-01', '2026-02-01']);
});

it('tidak membuat ulang periode yang sudah ada', function () {
    $this->service->proses($this->kontrak);
    $this->service->proses($this->kontrak);
    $this->service->proses($this->kontrak);

    expect(Penggajian::where('kontrak_id', $this->kontrak->id)->count())->toBe(2);
});

it('membayar gaji penuh untuk pekerja yang hadir sebulan penuh', function () {
    $this->service->proses($this->kontrak);

    $detail = Penggajian::where('periode', '2026-01-01')->first()
        ->penggajianDetails()->first();

    expect((float) $detail->gaji_pokok)->toEqual(10_000_000.0)
        ->and((float) $detail->bpjs)->toEqual(500_000.0)
        ->and((float) $detail->total_gaji)->toEqual(9_500_000.0)
        ->and($detail->hari_aktif)->toBe(31)
        ->and($detail->hari_periode)->toBe(31);
});

it('menghitung gaji proporsional untuk pekerja yang masuk di tengah bulan', function () {
    KontrakKaryawan::where('kontrak_id', $this->kontrak->id)
        ->update(['tanggal_mulai' => '2026-01-16']);

    $this->service->proses($this->kontrak);

    $detail = Penggajian::where('periode', '2026-01-01')->first()
        ->penggajianDetails()->first();

    // 16 sampai 31 Januari adalah 16 hari dari 31
    expect($detail->hari_aktif)->toBe(16)
        ->and((float) $detail->gaji_pokok)->toEqual(round(10_000_000 * 16 / 31))
        ->and((float) $detail->bpjs)->toEqual(round(round(10_000_000 * 16 / 31) * 0.05));

    // Februari tetap penuh
    $februari = Penggajian::where('periode', '2026-02-01')->first()
        ->penggajianDetails()->first();

    expect((float) $februari->gaji_pokok)->toEqual(10_000_000.0);
});

it('menghitung bpjs sebagai persentase gaji pokok setelah prorata', function () {
    $this->jabatan->update(['bpjs_persen' => 10.0]);

    $this->service->proses($this->kontrak);

    $detail = Penggajian::where('periode', '2026-01-01')->first()
        ->penggajianDetails()->first();

    expect((float) $detail->bpjs)->toEqual(1_000_000.0)
        ->and((float) $detail->total_gaji)->toEqual(9_000_000.0);
});

it('memotong cashbon secara mencicil sebatas plafon tiap periode', function () {
    Cashbon::factory()->jumlah(12_000_000)->create([
        'karyawan_id' => $this->karyawan->id,
    ]);

    $this->service->proses($this->kontrak);

    // Plafon per periode: 50% dari gaji bersih 9.500.000
    $januari = Penggajian::where('periode', '2026-01-01')->first()->penggajianDetails()->first();
    $februari = Penggajian::where('periode', '2026-02-01')->first()->penggajianDetails()->first();

    expect((float) $januari->potongan_cashbon)->toEqual(4_750_000.0)
        ->and((float) $januari->total_gaji)->toEqual(4_750_000.0)
        ->and((float) $februari->potongan_cashbon)->toEqual(4_750_000.0);

    // Sisa hutang terbawa ke periode berikutnya
    expect(Cashbon::first()->sisa())->toEqual(2_500_000.0)
        ->and(Cashbon::first()->status)->toBe(StatusCashbon::Berjalan);
});

it('mencatat setiap potongan di buku besar dan cocok dengan ringkasan detail', function () {
    Cashbon::factory()->jumlah(12_000_000)->create([
        'karyawan_id' => $this->karyawan->id,
    ]);

    $this->service->proses($this->kontrak);

    foreach (Penggajian::with('penggajianDetails')->get() as $penggajian) {
        foreach ($penggajian->penggajianDetails as $detail) {
            $bukuBesar = (float) CashbonPotongan::where('penggajian_detail_id', $detail->id)->sum('jumlah');

            expect($bukuBesar)->toEqual((float) $detail->potongan_cashbon);
        }
    }
});

it('menghasilkan slip yang selalu menjumlah tepat', function () {
    Cashbon::factory()->jumlah(7_000_000)->create([
        'karyawan_id' => $this->karyawan->id,
    ]);

    KontrakKaryawan::where('kontrak_id', $this->kontrak->id)
        ->update(['tanggal_mulai' => '2026-01-16']);

    $this->service->proses($this->kontrak);

    foreach (Penggajian::with('penggajianDetails')->get() as $penggajian) {
        foreach ($penggajian->penggajianDetails as $detail) {
            $hitung = (float) $detail->gaji_pokok - (float) $detail->bpjs - (float) $detail->potongan_cashbon;

            expect($hitung)->toEqual((float) $detail->total_gaji);
        }

        expect((float) $penggajian->total_gaji)
            ->toEqual((float) $penggajian->penggajianDetails->sum('total_gaji'));
    }
});

it('memotong dari cashbon terlama lebih dahulu', function () {
    $lama = Cashbon::factory()->jumlah(3_000_000)->create([
        'karyawan_id' => $this->karyawan->id,
        'created_at' => '2026-01-02 08:00:00',
    ]);

    $baru = Cashbon::factory()->jumlah(3_000_000)->create([
        'karyawan_id' => $this->karyawan->id,
        'created_at' => '2026-01-20 08:00:00',
    ]);

    $this->service->proses($this->kontrak);

    $januari = Penggajian::where('periode', '2026-01-01')->first()->penggajianDetails()->first();

    // Plafon 4.750.000: cashbon lama terpotong penuh, sisanya baru sebagian
    expect((float) CashbonPotongan::where('cashbon_id', $lama->id)
        ->where('penggajian_detail_id', $januari->id)->sum('jumlah'))->toEqual(3_000_000.0)
        ->and((float) CashbonPotongan::where('cashbon_id', $baru->id)
            ->where('penggajian_detail_id', $januari->id)->sum('jumlah'))->toEqual(1_750_000.0);

    // Sudah habis dialokasikan, tetapi belum berstatus lunas — penggajiannya
    // belum dibayar, jadi uangnya belum benar-benar dipotong
    expect($lama->fresh()->sisa())->toEqual(0.0)
        ->and($lama->fresh()->status)->toBe(StatusCashbon::Berjalan);
});

it('menandai cashbon lunas hanya setelah penggajiannya dibayar', function () {
    Cashbon::factory()->jumlah(3_000_000)->create([
        'karyawan_id' => $this->karyawan->id,
    ]);

    $this->service->proses($this->kontrak);

    $cashbon = Cashbon::first();
    expect($cashbon->fresh()->status)->toBe(StatusCashbon::Berjalan);

    $this->service->tandaiDibayar(Penggajian::where('periode', '2026-01-01')->first());

    expect($cashbon->fresh()->status)->toBe(StatusCashbon::Lunas)
        ->and($cashbon->fresh()->sisaHutang())->toEqual(0.0);
});

it('mengabaikan plafon dan melunasi sisa hutang pada periode terakhir kontrak', function () {
    Carbon::setTestNow('2026-08-01');

    Cashbon::factory()->jumlah(28_000_000)->create([
        'karyawan_id' => $this->karyawan->id,
    ]);

    $this->service->proses($this->kontrak);

    // Lima periode pertama memotong 4.750.000, menyisakan 4.250.000
    $juni = Penggajian::where('periode', '2026-06-01')->first();

    expect($juni->final)->toBeTrue()
        ->and((float) $juni->penggajianDetails()->first()->potongan_cashbon)->toEqual(4_250_000.0);

    // Seluruh hutang sudah teralokasi habis di enam periode
    expect(Cashbon::first()->sisa())->toEqual(0.0);

    foreach (Penggajian::orderBy('periode')->get() as $penggajian) {
        $this->service->tandaiDibayar($penggajian);
    }

    expect(Cashbon::first()->fresh()->status)->toBe(StatusCashbon::Lunas);
});

it('menolak menghitung ulang penggajian yang sudah dibayar', function () {
    $this->service->proses($this->kontrak);

    $penggajian = Penggajian::where('periode', '2026-01-01')->first();
    $this->service->tandaiDibayar($penggajian);

    expect($penggajian->fresh()->status)->toBe(StatusPenggajian::Dibayar);

    $this->service->hitungUlang($penggajian->fresh());
})->throws(PenggajianTerkunci::class);

it('tidak memberi baris detail kepada pekerja yang penempatannya belum menyentuh periode', function () {
    KontrakKaryawan::where('kontrak_id', $this->kontrak->id)
        ->update(['tanggal_mulai' => '2026-02-01']);

    $this->service->proses($this->kontrak);

    expect(Penggajian::where('periode', '2026-01-01')->first()->penggajianDetails()->count())->toBe(0)
        ->and(Penggajian::where('periode', '2026-02-01')->first()->penggajianDetails()->count())->toBe(1);
});

it('menghitung ulang menghasilkan angka yang sama persis', function () {
    Cashbon::factory()->jumlah(12_000_000)->create([
        'karyawan_id' => $this->karyawan->id,
    ]);

    $this->service->proses($this->kontrak);

    $penggajian = Penggajian::where('periode', '2026-01-01')->first();
    $sebelum = $penggajian->penggajianDetails()->first()->only([
        'gaji_pokok', 'bpjs', 'potongan_cashbon', 'total_gaji',
    ]);

    $this->service->hitungUlang($penggajian);
    $this->service->hitungUlang($penggajian->fresh());

    $sesudah = $penggajian->fresh()->penggajianDetails()->first()->only([
        'gaji_pokok', 'bpjs', 'potongan_cashbon', 'total_gaji',
    ]);

    expect($sesudah)->toEqual($sebelum);

    // Alokasi tidak menggandakan diri saat disusun ulang
    expect(CashbonPotongan::count())->toBe(2);
});
