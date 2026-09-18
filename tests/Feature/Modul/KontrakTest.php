<?php

use App\Enums\StatusCashbon;
use App\Enums\StatusKaryawan;
use App\Enums\StatusKontrak;
use App\Models\Cashbon;
use App\Models\CashbonPotongan;
use App\Models\Client;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\Kontrak;
use App\Models\KontrakKaryawan;
use App\Services\PenggajianService;
use Carbon\Carbon;

/**
 * Modul kontrak: daftar, buat, ubah, hapus, halaman detail, dan penjagaan izin.
 *
 * Aturan yang mengunci jadwal setelah penggajian diproses diuji terpisah di
 * IntegritasDataTest; berkas ini menjaga jalur CRUD dan bentuk datanya.
 */
beforeEach(function () {
    Carbon::setTestNow('2026-04-01');

    $this->izin = ['kontraks index', 'kontraks create', 'kontraks edit', 'kontraks delete'];
});

afterEach(function () {
    Carbon::setTestNow();
});

/**
 * @return array<string, mixed>
 */
function kirimanKontrak(Client $client, array $timpa = []): array
{
    return array_merge([
        'client_id' => $client->id,
        'judul' => 'Penyediaan tenaga kebersihan',
        'deskripsi' => 'Kebersihan gedung A dan B',
        'tanggal_mulai' => '2026-01-01',
        'tanggal_selesai' => '2026-12-31',
        'tanggal_gajian' => 25,
        'status' => 'Progres',
        'total_biaya' => 500_000_000,
    ], $timpa);
}

it('menampilkan daftar kontrak', function () {
    Kontrak::factory()->count(3)->create();

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->get(route('kontraks.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('kontraks/index')->has('kontraks.data', 3));
});

it('mengirim opsi status dari enum ke formulir tambah', function () {
    Client::factory()->create();

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->get(route('kontraks.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('kontraks/create')
            ->has('clients')
            ->has('opsi.status', count(StatusKontrak::cases())));
});

it('menyimpan kontrak baru', function () {
    $client = Client::factory()->create();

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->post(route('kontraks.store'), kirimanKontrak($client))
        ->assertRedirect(route('kontraks.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('kontraks', ['judul' => 'Penyediaan tenaga kebersihan', 'tanggal_gajian' => 25]);
});

it('menolak kontrak dengan masukan tidak sah', function (array $timpa, array $galat) {
    $client = Client::factory()->create();

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->post(route('kontraks.store'), kirimanKontrak($client, $timpa))
        ->assertSessionHasErrors($galat);

    expect(Kontrak::count())->toBe(0);
})->with([
    'client tidak ada' => [['client_id' => 9999], ['client_id']],
    'selesai mendahului mulai' => [['tanggal_selesai' => '2025-12-31'], ['tanggal_selesai']],
    'selesai sama dengan mulai' => [['tanggal_selesai' => '2026-01-01'], ['tanggal_selesai']],
    'tanggal gajian nol' => [['tanggal_gajian' => 0], ['tanggal_gajian']],
    'tanggal gajian lebih dari 31' => [['tanggal_gajian' => 32], ['tanggal_gajian']],
    'status di luar enum' => [['status' => 'aktif'], ['status']],
    'biaya negatif' => [['total_biaya' => -1], ['total_biaya']],
]);

it('menyajikan halaman detail dengan tanggal yang sudah diformat', function () {
    $kontrak = Kontrak::factory()->periode('2026-01-01', '2026-06-30')->create();

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->get(route('kontraks.show', $kontrak))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('kontraks/show')
            ->where('kontrak.tanggal_mulai', '2026-01-01')
            ->where('kontrak.tanggal_selesai', '2026-06-30')
            ->has('kontrak.penggajians')
            ->has('kontrak.kontrak_dokumens')
            ->has('kontrak.kontrak_karyawans')
            ->has('penggajian_summary'));
});

it('memperbarui kontrak', function () {
    $kontrak = Kontrak::factory()->periode('2026-01-01', '2026-12-31')->create();

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->put(route('kontraks.update', $kontrak), kirimanKontrak($kontrak->client, [
            'judul' => 'Judul Diperbarui',
        ]))
        ->assertRedirect(route('kontraks.index'))
        ->assertSessionHas('success');

    expect($kontrak->fresh()->judul)->toBe('Judul Diperbarui');
});

it('melepas seluruh pekerja saat kontrak ditandai selesai', function () {
    $kontrak = Kontrak::factory()->periode('2026-01-01', '2026-12-31')->create();
    $karyawan = Karyawan::factory()->aktif()->create();

    KontrakKaryawan::factory()->create([
        'kontrak_id' => $kontrak->id,
        'karyawan_id' => $karyawan->id,
        'tanggal_mulai' => '2026-01-01',
    ]);

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->put(route('kontraks.update', $kontrak), kirimanKontrak($kontrak->client, ['status' => 'Selesai']))
        ->assertRedirect(route('kontraks.index'));

    expect($karyawan->fresh()->status)->toBe(StatusKaryawan::NonAktif)
        ->and($kontrak->fresh()->status)->toBe(StatusKontrak::Selesai);
});

it('menghapus kontrak yang belum punya penggajian terbayar', function () {
    $kontrak = Kontrak::factory()->create();

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->delete(route('kontraks.destroy', $kontrak))
        ->assertRedirect(route('kontraks.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('kontraks', ['id' => $kontrak->id]);
});

it('E1 — menghapus kontrak yang penggajiannya belum dibayar tanpa merusak status cashbon', function () {
    /*
    | Menghapus kontrak menyeret penggajian dan detailnya, dan lewat cascade
    | ikut melepas buku besar potongan cashbon. Statusnya harus tetap benar:
    | Lunas diturunkan dari yang sudah TERBAYAR, dan penggajian terbayar sudah
    | ditolak lebih dahulu oleh penjagaan di atas.
    */
    Carbon::setTestNow('2026-02-25');

    $jabatan = Jabatan::factory()->gaji(10_000_000, 5.0)->create();
    $karyawan = Karyawan::factory()->aktif()->create(['id_jabatan' => $jabatan->id]);
    $kontrak = Kontrak::factory()->periode('2026-01-01', '2026-06-30', 25)->create();

    KontrakKaryawan::factory()->create([
        'kontrak_id' => $kontrak->id,
        'karyawan_id' => $karyawan->id,
        'tanggal_mulai' => '2026-01-01',
    ]);

    $cashbon = Cashbon::factory()->jumlah(2_000_000)->create(['karyawan_id' => $karyawan->id]);

    app(PenggajianService::class)->proses($kontrak);
    expect(CashbonPotongan::count())->toBe(1);

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->delete(route('kontraks.destroy', $kontrak))
        ->assertRedirect(route('kontraks.index'))
        ->assertSessionHas('success');

    expect(CashbonPotongan::count())->toBe(0)
        ->and($cashbon->fresh()->status)->toBe(StatusCashbon::Berjalan)
        ->and($cashbon->fresh()->sisaHutang())->toEqual(2_000_000.0)
        ->and($karyawan->fresh()->status)->toBe(StatusKaryawan::NonAktif);
});

it('menjawab 404 untuk kontrak yang tidak ada', function () {
    $this->actingAs(penggunaDenganIzin($this->izin))
        ->get(route('kontraks.show', 9999))
        ->assertNotFound();
});

it('menolak setiap aksi kontrak tanpa izin', function (string $metode, string $rute, bool $perluId) {
    $kontrak = Kontrak::factory()->create();

    $this->actingAs(penggunaTanpaIzin())
        ->{$metode}(route($rute, $perluId ? [$kontrak->id] : []))
        ->assertForbidden();
})->with([
    'daftar' => ['get', 'kontraks.index', false],
    'formulir tambah' => ['get', 'kontraks.create', false],
    'simpan' => ['post', 'kontraks.store', false],
    // Halaman detail dahulu sama sekali tanpa penjagaan izin: siapa pun yang
    // login bisa membaca daftar pekerja beserta NIK dan seluruh angka
    // penggajian kontrak itu.
    'halaman detail' => ['get', 'kontraks.show', true],
    'formulir ubah' => ['get', 'kontraks.edit', true],
    'perbarui' => ['put', 'kontraks.update', true],
    'hapus' => ['delete', 'kontraks.destroy', true],
]);
