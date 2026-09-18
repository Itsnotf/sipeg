<?php

use App\Models\Jabatan;
use App\Models\Karyawan;

/**
 * Modul jabatan: daftar, buat, ubah, hapus, dan penjagaan izin.
 *
 * Jabatan memegang gaji pokok dan persentase BPJS, jadi validasi angkanya
 * langsung menentukan benar-tidaknya setiap slip yang tersusun kemudian.
 */
beforeEach(function () {
    $this->izin = ['jabatans index', 'jabatans create', 'jabatans edit', 'jabatans delete'];
});

it('menampilkan daftar jabatan', function () {
    Jabatan::factory()->count(3)->create();

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->get(route('jabatans.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('jabatans/index')->has('jabatans.data', 3));
});

it('menyaring daftar jabatan lewat pencarian', function () {
    Jabatan::factory()->create(['nama_jabatan' => 'Cleaning Service']);
    Jabatan::factory()->create(['nama_jabatan' => 'Security']);

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->get(route('jabatans.index', ['search' => 'Clean']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('jabatans.data', 1));
});

it('membuka formulir tambah jabatan', function () {
    $this->actingAs(penggunaDenganIzin($this->izin))
        ->get(route('jabatans.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('jabatans/create'));
});

it('menyimpan jabatan baru', function () {
    $this->actingAs(penggunaDenganIzin($this->izin))
        ->post(route('jabatans.store'), [
            'nama_jabatan' => 'Cleaning Service',
            'deskripsi' => 'Kebersihan area kantor',
            'gaji' => 3_500_000,
            'bpjs_persen' => 5,
        ])
        ->assertRedirect(route('jabatans.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('jabatans', ['nama_jabatan' => 'Cleaning Service', 'bpjs_persen' => 5]);
});

it('menolak jabatan tanpa nama dan dengan persentase di luar rentang', function (array $kiriman, array $galat) {
    $this->actingAs(penggunaDenganIzin($this->izin))
        ->post(route('jabatans.store'), $kiriman)
        ->assertSessionHasErrors($galat);

    expect(Jabatan::count())->toBe(0);
})->with([
    'nama kosong' => [
        ['nama_jabatan' => '', 'deskripsi' => 'x', 'gaji' => 1, 'bpjs_persen' => 5],
        ['nama_jabatan'],
    ],
    'gaji negatif' => [
        ['nama_jabatan' => 'x', 'deskripsi' => 'x', 'gaji' => -1, 'bpjs_persen' => 5],
        ['gaji'],
    ],
    'bpjs di atas seratus' => [
        ['nama_jabatan' => 'x', 'deskripsi' => 'x', 'gaji' => 1, 'bpjs_persen' => 101],
        ['bpjs_persen'],
    ],
    'bpjs bukan angka' => [
        ['nama_jabatan' => 'x', 'deskripsi' => 'x', 'gaji' => 1, 'bpjs_persen' => 'lima'],
        ['bpjs_persen'],
    ],
]);

it('membuka formulir ubah jabatan', function () {
    $jabatan = Jabatan::factory()->create();

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->get(route('jabatans.edit', $jabatan))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('jabatans/edit')->where('jabatan.id', $jabatan->id));
});

it('memperbarui jabatan', function () {
    $jabatan = Jabatan::factory()->gaji(3_000_000)->create();

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->put(route('jabatans.update', $jabatan), [
            'nama_jabatan' => 'Supervisor',
            'deskripsi' => 'Pengawas lapangan',
            'gaji' => 6_000_000,
            'bpjs_persen' => 4,
        ])
        ->assertRedirect(route('jabatans.index'))
        ->assertSessionHas('success');

    expect($jabatan->fresh()->nama_jabatan)->toBe('Supervisor')
        ->and((float) $jabatan->fresh()->gaji)->toBe(6_000_000.0);
});

it('menghapus jabatan', function () {
    $jabatan = Jabatan::factory()->create();

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->delete(route('jabatans.destroy', $jabatan))
        ->assertRedirect(route('jabatans.index'));

    $this->assertDatabaseMissing('jabatans', ['id' => $jabatan->id]);
});

it('menolak penghapusan jabatan yang masih dipakai karyawan', function () {
    // karyawans.id_jabatan memakai cascade: tanpa penjagaan ini, menghapus
    // jabatan ikut menghapus orangnya beserta riwayat gajinya.
    $jabatan = Jabatan::factory()->create();
    $karyawan = Karyawan::factory()->create(['id_jabatan' => $jabatan->id]);

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->delete(route('jabatans.destroy', $jabatan))
        ->assertRedirect(route('jabatans.index'))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('jabatans', ['id' => $jabatan->id]);
    $this->assertDatabaseHas('karyawans', ['id' => $karyawan->id]);
});

it('menolak setiap aksi jabatan tanpa izin', function (string $metode, string $rute, bool $perluId) {
    $jabatan = Jabatan::factory()->create();

    $this->actingAs(penggunaTanpaIzin())
        ->{$metode}(route($rute, $perluId ? [$jabatan->id] : []))
        ->assertForbidden();
})->with([
    'daftar' => ['get', 'jabatans.index', false],
    'formulir tambah' => ['get', 'jabatans.create', false],
    'simpan' => ['post', 'jabatans.store', false],
    'formulir ubah' => ['get', 'jabatans.edit', true],
    'perbarui' => ['put', 'jabatans.update', true],
    'hapus' => ['delete', 'jabatans.destroy', true],
]);
