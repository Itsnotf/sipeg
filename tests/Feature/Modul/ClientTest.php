<?php

use App\Models\Client;
use App\Models\Kontrak;

/**
 * Modul client: daftar, buat, ubah, hapus, dan penjagaan izin.
 */
beforeEach(function () {
    $this->izin = ['clients index', 'clients create', 'clients edit', 'clients delete'];
});

it('menampilkan daftar client', function () {
    Client::factory()->count(3)->create();

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->get(route('clients.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('clients/index')->has('clients.data', 3));
});

it('menyaring daftar client lewat pencarian', function () {
    Client::factory()->create(['nama_client' => 'PT Sumber Makmur']);
    Client::factory()->create(['nama_client' => 'CV Bina Usaha']);

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->get(route('clients.index', ['search' => 'Sumber']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('clients.data', 1));
});

it('membuka formulir tambah client', function () {
    $this->actingAs(penggunaDenganIzin($this->izin))
        ->get(route('clients.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('clients/create'));
});

it('menyimpan client baru', function () {
    $this->actingAs(penggunaDenganIzin($this->izin))
        ->post(route('clients.store'), [
            'nama_client' => 'PT Sumber Makmur',
            'alamat' => 'Jl. Merdeka 1',
            'email' => 'kontak@sumbermakmur.co.id',
            'no_hp' => '081234567890',
            'deskripsi' => null,
        ])
        ->assertRedirect(route('clients.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('clients', ['email' => 'kontak@sumbermakmur.co.id']);
});

it('menolak client dengan masukan tidak sah', function (array $kiriman, array $galat) {
    Client::factory()->create(['email' => 'terpakai@contoh.co.id']);

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->post(route('clients.store'), $kiriman)
        ->assertSessionHasErrors($galat);

    expect(Client::count())->toBe(1);
})->with([
    'nama kosong' => [
        ['nama_client' => '', 'alamat' => 'x', 'email' => 'a@b.co', 'no_hp' => '08123'],
        ['nama_client'],
    ],
    'email bukan email' => [
        ['nama_client' => 'x', 'alamat' => 'x', 'email' => 'bukan-email', 'no_hp' => '08123'],
        ['email'],
    ],
    'email sudah dipakai' => [
        ['nama_client' => 'x', 'alamat' => 'x', 'email' => 'terpakai@contoh.co.id', 'no_hp' => '08123'],
        ['email'],
    ],
    'nomor hp terlalu panjang' => [
        ['nama_client' => 'x', 'alamat' => 'x', 'email' => 'a@b.co', 'no_hp' => str_repeat('8', 20)],
        ['no_hp'],
    ],
]);

it('membuka formulir ubah client', function () {
    $client = Client::factory()->create();

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->get(route('clients.edit', $client))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('clients/edit')->where('client.id', $client->id));
});

it('memperbarui client', function () {
    $client = Client::factory()->create();

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->put(route('clients.update', $client), [
            'nama_client' => 'PT Nama Baru',
            'alamat' => $client->alamat,
            'email' => $client->email,
            'no_hp' => $client->no_hp,
            'deskripsi' => $client->deskripsi,
        ])
        ->assertRedirect(route('clients.index'))
        ->assertSessionHas('success');

    expect($client->fresh()->nama_client)->toBe('PT Nama Baru');
});

it('mengizinkan client menyimpan emailnya sendiri saat diubah', function () {
    $client = Client::factory()->create(['email' => 'tetap@contoh.co.id']);

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->put(route('clients.update', $client), [
            'nama_client' => 'Nama Lain',
            'alamat' => 'Jl. Baru 2',
            'email' => 'tetap@contoh.co.id',
            'no_hp' => '081234567890',
            'deskripsi' => null,
        ])
        ->assertSessionHasNoErrors();
});

it('menghapus client', function () {
    $client = Client::factory()->create();

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->delete(route('clients.destroy', $client))
        ->assertRedirect(route('clients.index'));

    $this->assertDatabaseMissing('clients', ['id' => $client->id]);
});

it('menolak penghapusan client yang masih punya kontrak', function () {
    // kontraks.client_id memakai cascade: tanpa penjagaan ini, menghapus client
    // menyeret kontrak beserta penggajian yang sudah dibayar di bawahnya.
    $client = Client::factory()->create();
    $kontrak = Kontrak::factory()->create(['client_id' => $client->id]);

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->delete(route('clients.destroy', $client))
        ->assertRedirect(route('clients.index'))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('clients', ['id' => $client->id]);
    $this->assertDatabaseHas('kontraks', ['id' => $kontrak->id]);
});

it('menolak setiap aksi client tanpa izin', function (string $metode, string $rute, bool $perluId) {
    $client = Client::factory()->create();

    $this->actingAs(penggunaTanpaIzin())
        ->{$metode}(route($rute, $perluId ? [$client->id] : []))
        ->assertForbidden();
})->with([
    'daftar' => ['get', 'clients.index', false],
    'formulir tambah' => ['get', 'clients.create', false],
    'simpan' => ['post', 'clients.store', false],
    'formulir ubah' => ['get', 'clients.edit', true],
    'perbarui' => ['put', 'clients.update', true],
    'hapus' => ['delete', 'clients.destroy', true],
]);
