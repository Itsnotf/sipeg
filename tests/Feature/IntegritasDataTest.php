<?php

use App\Models\Cashbon;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\Kontrak;
use App\Models\KontrakKaryawan;
use App\Models\Penggajian;
use App\Models\User;
use App\Services\CashbonService;
use App\Services\PenggajianService;
use Carbon\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Penjaga yang mencegah riwayat penggajian terbayar rusak dari pintu belakang.
 *
 * Seluruh tabel terkait memakai penghapusan berantai, sehingga menghapus satu
 * karyawan atau kontrak dapat melenyapkan penggajian yang uangnya sudah
 * berpindah beserta buku besar potongannya — membuat hutang yang sudah lunas
 * hidup kembali.
 */
beforeEach(function () {
    Carbon::setTestNow('2026-04-01');

    foreach (['kontraks edit', 'kontraks delete', 'karyawans delete'] as $izin) {
        Permission::findOrCreate($izin);
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->user = User::factory()->create();
    $this->user->givePermissionTo(['kontraks edit', 'kontraks delete', 'karyawans delete']);
    $this->actingAs($this->user);

    $jabatan = Jabatan::factory()->gaji(10_000_000, 5.0)->create();
    $this->karyawan = Karyawan::factory()->create(['id_jabatan' => $jabatan->id]);
    $this->kontrak = Kontrak::factory()->periode('2026-01-01', '2026-06-30', 25)->create();

    KontrakKaryawan::factory()->create([
        'kontrak_id' => $this->kontrak->id,
        'karyawan_id' => $this->karyawan->id,
        'tanggal_mulai' => '2026-01-01',
    ]);

    $this->penggajian = app(PenggajianService::class);
    $this->penggajian->proses($this->kontrak);
});

afterEach(function () {
    Carbon::setTestNow();
});

function ubahKontrak(Kontrak $kontrak, array $perubahan = []): array
{
    return array_merge([
        'client_id' => $kontrak->client_id,
        'judul' => $kontrak->judul,
        'deskripsi' => $kontrak->deskripsi,
        'tanggal_mulai' => '2026-01-01',
        'tanggal_selesai' => '2026-06-30',
        'tanggal_gajian' => 25,
        'total_biaya' => 500_000_000,
        'status' => 'Progres',
    ], $perubahan);
}

it('menolak perubahan tanggal gajian setelah penggajian diproses', function () {
    // Tanggal gajian baru menghasilkan kunci periode berbeda, sehingga batasan
    // unik tidak mencegah bulan yang sama dibayar dua kali
    $this->put(
        route('kontraks.update', $this->kontrak->id),
        ubahKontrak($this->kontrak, ['tanggal_gajian' => 10])
    )->assertSessionHas('error');

    expect($this->kontrak->fresh()->tanggal_gajian)->toBe(25);
});

it('menolak perubahan rentang tanggal kontrak setelah penggajian diproses', function () {
    $this->put(
        route('kontraks.update', $this->kontrak->id),
        ubahKontrak($this->kontrak, ['tanggal_selesai' => '2026-12-31'])
    )->assertSessionHas('error');

    expect($this->kontrak->fresh()->tanggal_selesai->format('Y-m-d'))->toBe('2026-06-30');
});

it('tetap mengizinkan perubahan bidang yang tidak memengaruhi jadwal', function () {
    $this->put(
        route('kontraks.update', $this->kontrak->id),
        ubahKontrak($this->kontrak, ['judul' => 'Judul Diperbarui'])
    )->assertRedirect(route('kontraks.index'));

    expect($this->kontrak->fresh()->judul)->toBe('Judul Diperbarui');
});

it('menolak penghapusan kontrak yang punya penggajian terbayar', function () {
    $this->penggajian->tandaiDibayar(Penggajian::where('periode', '2026-01-01')->firstOrFail());

    $this->delete(route('kontraks.destroy', $this->kontrak->id))->assertSessionHas('error');

    expect(Kontrak::whereKey($this->kontrak->id)->exists())->toBeTrue()
        ->and(Penggajian::count())->toBe(2);
});

it('menolak penghapusan karyawan yang punya riwayat penggajian terbayar', function () {
    $this->penggajian->tandaiDibayar(Penggajian::where('periode', '2026-01-01')->firstOrFail());

    $this->delete(route('karyawans.destroy', $this->karyawan->id))->assertSessionHas('error');

    expect(Karyawan::whereKey($this->karyawan->id)->exists())->toBeTrue();
});

it('menolak penghapusan cashbon yang sudah dipotong pada penggajian terbayar', function () {
    $service = app(CashbonService::class);

    $cashbon = $service->buat([
        'karyawan_id' => $this->karyawan->id,
        'jumlah' => 2_000_000,
        'keterangan' => 'Pinjaman',
    ]);

    $this->penggajian->tandaiDibayar(Penggajian::where('periode', '2026-01-01')->firstOrFail());

    expect(fn () => $service->hapus($cashbon->fresh()))
        ->toThrow(RuntimeException::class);

    expect(Cashbon::whereKey($cashbon->id)->exists())->toBeTrue();
});

it('menolak penurunan jumlah cashbon di bawah yang sudah terbayar', function () {
    $service = app(CashbonService::class);

    $cashbon = $service->buat([
        'karyawan_id' => $this->karyawan->id,
        'jumlah' => 2_000_000,
        'keterangan' => 'Pinjaman',
    ]);

    $this->penggajian->tandaiDibayar(Penggajian::where('periode', '2026-01-01')->firstOrFail());

    expect(fn () => $service->ubah($cashbon->fresh(), [
        'jumlah' => 500_000,
        'keterangan' => 'Dikecilkan',
    ]))->toThrow(RuntimeException::class);

    expect((float) $cashbon->fresh()->jumlah)->toEqual(2_000_000.0);
});
