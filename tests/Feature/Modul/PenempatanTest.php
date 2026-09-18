<?php

use App\Enums\StatusKaryawan;
use App\Enums\StatusKontrak;
use App\Models\Karyawan;
use App\Models\Kontrak;
use App\Models\KontrakKaryawan;
use Carbon\Carbon;

/**
 * Modul penempatan: menempatkan pekerja pada kontrak dan mengakhirinya.
 *
 * Dua aturan domain dijaga di sini. Satu pekerja hanya boleh punya satu
 * penempatan aktif — plafon potongan dihitung per penggajian sementara
 * hutangnya per pekerja, sehingga penempatan ganda menerapkan plafon yang sama
 * dua kali pada hutang yang sama. Dan penempatan diakhiri, bukan dihapus:
 * barisnya masih dibutuhkan untuk menghitung hari yang sudah dikerjakan.
 */
beforeEach(function () {
    Carbon::setTestNow('2026-04-10');

    $this->izin = ['kontraks karyawans index', 'kontraks karyawans create', 'kontraks karyawans delete'];
    $this->kontrak = Kontrak::factory()->periode('2026-01-01', '2026-12-31')->create();
});

afterEach(function () {
    Carbon::setTestNow();
});

it('menampilkan daftar penempatan sebuah kontrak', function () {
    KontrakKaryawan::factory()->count(2)->create(['kontrak_id' => $this->kontrak->id]);
    KontrakKaryawan::factory()->create();

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->get(route('kontraks.karyawans.index', $this->kontrak->id))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('kontraks/karyawans/index')
            ->has('karyawans.data', 2)
            ->where('kontrak.judul', $this->kontrak->judul));
});

it('hanya menawarkan pekerja yang belum ditempatkan', function () {
    $tersedia = Karyawan::factory()->create();
    $sudahAktif = Karyawan::factory()->aktif()->create();

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->get(route('kontraks.karyawans.create', $this->kontrak->id))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('kontraks/karyawans/create')
            ->has('karyawans', 1)
            ->where('karyawans.0.id', $tersedia->id));

    expect($sudahAktif->status)->toBe(StatusKaryawan::Aktif);
});

it('menempatkan beberapa pekerja sekaligus dan menandainya aktif', function () {
    $pekerja = Karyawan::factory()->count(2)->create();

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->post(route('kontraks.karyawans.store', $this->kontrak->id), [
            'karyawan_id' => $pekerja->pluck('id')->all(),
        ])
        ->assertRedirect(route('kontraks.karyawans.index', $this->kontrak->id))
        ->assertSessionHas('success');

    expect(KontrakKaryawan::where('kontrak_id', $this->kontrak->id)->count())->toBe(2)
        ->and($pekerja->first()->fresh()->status)->toBe(StatusKaryawan::Aktif);
});

it('memulai penempatan hari ini, bukan di tanggal mulai kontrak yang sudah lewat', function () {
    $karyawan = Karyawan::factory()->create();

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->post(route('kontraks.karyawans.store', $this->kontrak->id), [
            'karyawan_id' => [$karyawan->id],
        ]);

    expect(KontrakKaryawan::first()->tanggal_mulai->format('Y-m-d'))->toBe('2026-04-10');
});

it('memulai penempatan di tanggal mulai kontrak bila kontraknya belum berjalan', function () {
    $kontrakMendatang = Kontrak::factory()->periode('2026-09-01', '2027-08-31')->create();
    $karyawan = Karyawan::factory()->create();

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->post(route('kontraks.karyawans.store', $kontrakMendatang->id), [
            'karyawan_id' => [$karyawan->id],
        ]);

    expect(KontrakKaryawan::first()->tanggal_mulai->format('Y-m-d'))->toBe('2026-09-01');
});

it('menolak pekerja yang sudah terdaftar di kontrak yang sama', function () {
    $karyawan = Karyawan::factory()->create();

    KontrakKaryawan::factory()->create([
        'kontrak_id' => $this->kontrak->id,
        'karyawan_id' => $karyawan->id,
        'tanggal_mulai' => '2026-01-01',
    ]);

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->post(route('kontraks.karyawans.store', $this->kontrak->id), [
            'karyawan_id' => [$karyawan->id],
        ])
        ->assertSessionHas('error');

    expect(KontrakKaryawan::where('karyawan_id', $karyawan->id)->count())->toBe(1);
});

it('menolak pekerja yang masih punya penempatan aktif di kontrak lain', function () {
    $karyawan = Karyawan::factory()->aktif()->create();
    $kontrakLain = Kontrak::factory()->create();

    KontrakKaryawan::factory()->create([
        'kontrak_id' => $kontrakLain->id,
        'karyawan_id' => $karyawan->id,
        'tanggal_mulai' => '2026-01-01',
        'tanggal_selesai' => null,
    ]);

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->post(route('kontraks.karyawans.store', $this->kontrak->id), [
            'karyawan_id' => [$karyawan->id],
        ])
        ->assertSessionHas('error');

    expect(KontrakKaryawan::where('kontrak_id', $this->kontrak->id)->count())->toBe(0);
});

it('menerima pekerja yang penempatan sebelumnya sudah diakhiri', function () {
    $karyawan = Karyawan::factory()->create();
    $kontrakLama = Kontrak::factory()->create();

    KontrakKaryawan::factory()->create([
        'kontrak_id' => $kontrakLama->id,
        'karyawan_id' => $karyawan->id,
        'tanggal_mulai' => '2026-01-01',
        'tanggal_selesai' => '2026-03-31',
    ]);

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->post(route('kontraks.karyawans.store', $this->kontrak->id), [
            'karyawan_id' => [$karyawan->id],
        ])
        ->assertSessionHas('success');

    expect(KontrakKaryawan::where('kontrak_id', $this->kontrak->id)->count())->toBe(1);
});

it('G3 — menolak penempatan pada kontrak yang sudah berakhir', function () {
    $kontrakLampau = Kontrak::factory()->periode('2025-01-01', '2025-12-31')->create();
    $karyawan = Karyawan::factory()->create();

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->post(route('kontraks.karyawans.store', $kontrakLampau->id), ['karyawan_id' => [$karyawan->id]])
        ->assertSessionHas('error');

    expect(KontrakKaryawan::where('kontrak_id', $kontrakLampau->id)->count())->toBe(0)
        ->and($karyawan->fresh()->status)->toBe(StatusKaryawan::NonAktif);
});

it('menolak kiriman tanpa satu pun pekerja terpilih', function () {
    $this->actingAs(penggunaDenganIzin($this->izin))
        ->post(route('kontraks.karyawans.store', $this->kontrak->id), ['karyawan_id' => []])
        ->assertSessionHasErrors(['karyawan_id']);
});

it('menolak pekerja yang tidak ada dan pilihan ganda', function (array $kiriman) {
    $this->actingAs(penggunaDenganIzin($this->izin))
        ->post(route('kontraks.karyawans.store', $this->kontrak->id), $kiriman)
        ->assertSessionHasErrors();

    expect(KontrakKaryawan::count())->toBe(0);
})->with([
    'pekerja tidak ada' => [['karyawan_id' => [9999]]],
    'pilihan ganda' => [fn () => ['karyawan_id' => array_fill(0, 2, Karyawan::factory()->create()->id)]],
]);

it('mengakhiri penempatan alih-alih menghapus barisnya', function () {
    $karyawan = Karyawan::factory()->aktif()->create();

    $penempatan = KontrakKaryawan::factory()->create([
        'kontrak_id' => $this->kontrak->id,
        'karyawan_id' => $karyawan->id,
        'tanggal_mulai' => '2026-01-01',
    ]);

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->delete(route('kontraks.karyawans.destroy', [$this->kontrak->id, $penempatan->id]))
        ->assertRedirect(route('kontraks.karyawans.index', $this->kontrak->id))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('kontrak_karyawans', ['id' => $penempatan->id]);

    expect($penempatan->fresh()->tanggal_selesai->format('Y-m-d'))->toBe('2026-04-10')
        ->and($karyawan->fresh()->status)->toBe(StatusKaryawan::NonAktif);
});

it('memberi pesan jelas saat penempatan tidak ada di kontrak ini', function () {
    $this->actingAs(penggunaDenganIzin($this->izin))
        ->delete(route('kontraks.karyawans.destroy', [$this->kontrak->id, 9999]))
        ->assertRedirect(route('kontraks.karyawans.index', $this->kontrak->id))
        ->assertSessionHas('error');
});

it('B1 — menutup penempatan saat kontrak ditandai selesai, bukan hanya menonaktifkan pekerjanya', function () {
    $karyawan = Karyawan::factory()->aktif()->create();

    $penempatan = KontrakKaryawan::factory()->create([
        'kontrak_id' => $this->kontrak->id,
        'karyawan_id' => $karyawan->id,
        'tanggal_mulai' => '2026-01-01',
    ]);

    $izinKontrak = ['kontraks index', 'kontraks edit'];

    $this->actingAs(penggunaDenganIzin([...$this->izin, ...$izinKontrak]))
        ->put(route('kontraks.update', $this->kontrak), [
            'client_id' => $this->kontrak->client_id,
            'judul' => $this->kontrak->judul,
            'deskripsi' => $this->kontrak->deskripsi,
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-12-31',
            'tanggal_gajian' => 25,
            'status' => 'Selesai',
            'total_biaya' => (float) $this->kontrak->total_biaya,
        ])
        ->assertRedirect(route('kontraks.index'));

    expect($penempatan->fresh()->tanggal_selesai)->not->toBeNull()
        ->and($karyawan->fresh()->status)->toBe(StatusKaryawan::NonAktif);
});

it('B1 — melepaskan pekerja untuk ditempatkan di kontrak lain setelah kontraknya selesai', function () {
    $karyawan = Karyawan::factory()->aktif()->create();

    KontrakKaryawan::factory()->create([
        'kontrak_id' => $this->kontrak->id,
        'karyawan_id' => $karyawan->id,
        'tanggal_mulai' => '2026-01-01',
    ]);

    $this->kontrak->update(['status' => StatusKontrak::Selesai]);
    app(\App\Services\PenempatanKontrak::class)->akhiriSeluruhnya($this->kontrak->fresh());

    $kontrakBaru = Kontrak::factory()->periode('2026-01-01', '2026-12-31')->create();

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->post(route('kontraks.karyawans.store', $kontrakBaru->id), ['karyawan_id' => [$karyawan->id]])
        ->assertSessionHas('success');

    expect(KontrakKaryawan::where('kontrak_id', $kontrakBaru->id)->count())->toBe(1);
});

it('B3 — menerima pekerja yang penempatannya di kontrak ini sudah diakhiri', function () {
    $karyawan = Karyawan::factory()->create();

    KontrakKaryawan::factory()->create([
        'kontrak_id' => $this->kontrak->id,
        'karyawan_id' => $karyawan->id,
        'tanggal_mulai' => '2026-01-01',
        'tanggal_selesai' => '2026-02-28',
    ]);

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->post(route('kontraks.karyawans.store', $this->kontrak->id), ['karyawan_id' => [$karyawan->id]])
        ->assertSessionHas('success');

    expect(KontrakKaryawan::where('kontrak_id', $this->kontrak->id)->count())->toBe(2);
});

it('menolak setiap aksi penempatan tanpa izin', function (string $metode, string $rute, bool $perluPenempatan) {
    $penempatan = KontrakKaryawan::factory()->create(['kontrak_id' => $this->kontrak->id]);

    $parameter = $perluPenempatan ? [$this->kontrak->id, $penempatan->id] : [$this->kontrak->id];

    $this->actingAs(penggunaTanpaIzin())
        ->{$metode}(route($rute, $parameter))
        ->assertForbidden();
})->with([
    'daftar' => ['get', 'kontraks.karyawans.index', false],
    'formulir tambah' => ['get', 'kontraks.karyawans.create', false],
    'simpan' => ['post', 'kontraks.karyawans.store', false],
    'hapus' => ['delete', 'kontraks.karyawans.destroy', true],
]);
