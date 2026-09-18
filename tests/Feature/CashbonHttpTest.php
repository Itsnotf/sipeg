<?php

use App\Enums\StatusCashbon;
use App\Models\Cashbon;
use App\Models\CashbonPotongan;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\Kontrak;
use App\Models\KontrakKaryawan;
use App\Models\Penggajian;
use App\Models\User;
use App\Services\PenggajianService;
use Carbon\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Menguji cashbon lewat rute dan controller sungguhan, bukan lewat service.
 *
 * Bug terparah pada sistem lama justru lolos dari seluruh pengujian karena
 * setiap test membuat cashbon langsung melalui Eloquent, melewati controller
 * dan lapisan validasi. Jalur tulis menyimpan 'belum dibayar' sementara jalur
 * baca mencari 'belum_dibayar', sehingga potongan tidak pernah terjadi — dan
 * test tetap hijau. Berkas inilah yang menutup celah tersebut.
 */
beforeEach(function () {
    Carbon::setTestNow('2026-04-01');

    foreach (['cashbons index', 'cashbons create', 'cashbons edit', 'cashbons delete'] as $izin) {
        Permission::findOrCreate($izin);
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->user = User::factory()->create();
    $this->user->givePermissionTo(['cashbons index', 'cashbons create', 'cashbons edit', 'cashbons delete']);
    $this->actingAs($this->user);

    $jabatan = Jabatan::factory()->gaji(10_000_000, 5.0)->create();

    $this->karyawan = Karyawan::factory()->create(['id_jabatan' => $jabatan->id]);

    $this->kontrak = Kontrak::factory()->periode('2026-01-01', '2026-06-30', 25)->create();

    KontrakKaryawan::factory()->create([
        'kontrak_id' => $this->kontrak->id,
        'karyawan_id' => $this->karyawan->id,
        'tanggal_mulai' => '2026-01-01',
    ]);

    app(PenggajianService::class)->proses($this->kontrak);
});

afterEach(function () {
    Carbon::setTestNow();
});

it('memotong gaji saat cashbon dibuat lewat formulir', function () {
    $response = $this->post(route('cashbons.store'), [
        'karyawan_id' => $this->karyawan->id,
        'jumlah' => '3000000',
        'keterangan' => 'Pinjaman biaya sekolah',
    ]);

    $response->assertRedirect(route('cashbons.index'));

    $cashbon = Cashbon::firstOrFail();

    expect($cashbon->status)->toBe(StatusCashbon::Berjalan)
        ->and((float) $cashbon->jumlah)->toEqual(3_000_000.0);

    // Yang menentukan bukan angka ringkasan di detail, melainkan ada tidaknya
    // baris buku besar — ringkasan bisa saja kebetulan benar
    $bukuBesar = CashbonPotongan::where('cashbon_id', $cashbon->id)->get();

    expect($bukuBesar)->toHaveCount(1)
        ->and((float) $bukuBesar->first()->jumlah)->toEqual(3_000_000.0);

    $detail = Penggajian::where('periode', '2026-01-01')->first()->penggajianDetails()->first();

    expect((float) $detail->potongan_cashbon)->toEqual(3_000_000.0)
        ->and((float) $detail->total_gaji)->toEqual(6_500_000.0);
});

it('menolak cashbon yang melebihi plafon pinjaman', function () {
    // Plafon: 3 kali gaji bersih 9.500.000 = 28.500.000
    $response = $this->post(route('cashbons.store'), [
        'karyawan_id' => $this->karyawan->id,
        'jumlah' => '30000000',
        'keterangan' => 'Pinjaman terlalu besar',
    ]);

    $response->assertSessionHas('error');

    expect(Cashbon::count())->toBe(0)
        ->and(CashbonPotongan::count())->toBe(0);
});

it('menolak jumlah cashbon yang bukan angka', function () {
    $response = $this->post(route('cashbons.store'), [
        'karyawan_id' => $this->karyawan->id,
        'jumlah' => 'seratus ribu',
        'keterangan' => 'Tidak valid',
    ]);

    $response->assertSessionHasErrors('jumlah');
    expect(Cashbon::count())->toBe(0);
});

it('menghitung ulang potongan saat cashbon diubah lewat formulir', function () {
    $this->post(route('cashbons.store'), [
        'karyawan_id' => $this->karyawan->id,
        'jumlah' => '3000000',
        'keterangan' => 'Pinjaman awal',
    ]);

    $cashbon = Cashbon::firstOrFail();

    $this->put(route('cashbons.update', $cashbon->id), [
        'karyawan_id' => $this->karyawan->id,
        'jumlah' => '1000000',
        'keterangan' => 'Pinjaman direvisi',
    ])->assertRedirect(route('cashbons.index'));

    $detail = Penggajian::where('periode', '2026-01-01')->first()->penggajianDetails()->first();

    expect((float) $detail->potongan_cashbon)->toEqual(1_000_000.0)
        ->and((float) $detail->total_gaji)->toEqual(8_500_000.0);

    // Alokasi lama dilepas, bukan ditumpuk
    expect(CashbonPotongan::where('cashbon_id', $cashbon->id)->count())->toBe(1);
});

it('mengembalikan gaji utuh saat cashbon dihapus lewat formulir', function () {
    $this->post(route('cashbons.store'), [
        'karyawan_id' => $this->karyawan->id,
        'jumlah' => '3000000',
        'keterangan' => 'Pinjaman yang dibatalkan',
    ]);

    $cashbon = Cashbon::firstOrFail();

    $this->delete(route('cashbons.destroy', $cashbon->id))
        ->assertRedirect(route('cashbons.index'));

    $detail = Penggajian::where('periode', '2026-01-01')->first()->penggajianDetails()->first();

    expect((float) $detail->potongan_cashbon)->toEqual(0.0)
        ->and((float) $detail->total_gaji)->toEqual(9_500_000.0)
        ->and(CashbonPotongan::count())->toBe(0);
});

it('B5 — memindahkan cashbon ke karyawan lain saat peminjamnya dikoreksi', function () {
    /*
    | Dropdown peminjam di formulir edit dahulu tidak berefek apa pun: nilainya
    | divalidasi, dikirim, lalu dibuang. Cashbon yang tercatat atas nama yang
    | salah tampak berhasil dikoreksi padahal hutangnya tidak berpindah.
    */
    $lain = Karyawan::factory()->create(['id_jabatan' => $this->karyawan->id_jabatan]);

    KontrakKaryawan::factory()->create([
        'kontrak_id' => $this->kontrak->id,
        'karyawan_id' => $lain->id,
        'tanggal_mulai' => '2026-01-01',
    ]);

    app(PenggajianService::class)->hitungUlangUntukKaryawan($lain->id);

    $this->post(route('cashbons.store'), [
        'karyawan_id' => $this->karyawan->id,
        'jumlah' => '2000000',
        'keterangan' => 'Salah orang',
    ]);

    $cashbon = Cashbon::firstOrFail();

    $this->put(route('cashbons.update', $cashbon->id), [
        'karyawan_id' => $lain->id,
        'jumlah' => '2000000',
        'keterangan' => 'Dikoreksi ke peminjam yang benar',
    ])->assertRedirect(route('cashbons.index'));

    expect((int) $cashbon->fresh()->karyawan_id)->toBe($lain->id);

    $januari = Penggajian::where('periode', '2026-01-01')->firstOrFail();

    $potonganLama = $januari->penggajianDetails()
        ->where('karyawan_id', $this->karyawan->id)
        ->first();

    $potonganBaru = $januari->penggajianDetails()
        ->where('karyawan_id', $lain->id)
        ->first();

    expect((float) $potonganLama->potongan_cashbon)->toEqual(0.0)
        ->and((float) $potonganBaru->potongan_cashbon)->toEqual(2_000_000.0);
});

it('menolak pengguna tanpa izin membuat cashbon', function () {
    $orangLain = User::factory()->create();

    $this->actingAs($orangLain)->post(route('cashbons.store'), [
        'karyawan_id' => $this->karyawan->id,
        'jumlah' => '1000000',
        'keterangan' => 'Tanpa izin',
    ])->assertForbidden();

    expect(Cashbon::count())->toBe(0);
});
