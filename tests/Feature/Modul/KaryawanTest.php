<?php

use App\Enums\StatusKaryawan;
use App\Enums\StatusPenggajian;
use App\Models\Cashbon;
use App\Models\CashbonPotongan;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\Kontrak;
use App\Models\KontrakKaryawan;
use App\Models\Penggajian;
use App\Models\PenggajianDetail;
use App\Services\PenggajianService;
use Carbon\Carbon;

/**
 * Modul karyawan: daftar, buat, ubah, hapus, dan penjagaan izin.
 *
 * Aturan domainnya satu: karyawan yang pernah masuk penggajian terbayar tidak
 * boleh dihapus, karena penghapusannya ikut menyeret baris slip dan buku besar
 * potongan — riwayat uang yang sudah berpindah.
 */
beforeEach(function () {
    $this->izin = ['karyawans index', 'karyawans create', 'karyawans edit', 'karyawans delete'];
});

/**
 * @return array<string, mixed>
 */
function kirimanKaryawan(Jabatan $jabatan, array $timpa = []): array
{
    return array_merge([
        'id_jabatan' => $jabatan->id,
        'nama' => 'Budi Santoso',
        'nik' => '3201010101010001',
        'alamat' => 'Jl. Mawar 3',
        'tanggal_lahir' => '1995-04-17',
        'jenis_kelamin' => 'L',
        'no_hp' => '081234567890',
    ], $timpa);
}

it('menampilkan daftar karyawan', function () {
    Karyawan::factory()->count(3)->create();

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->get(route('karyawans.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('karyawans/index')->has('karyawans.data', 3));
});

it('menyaring daftar karyawan lewat nama maupun NIK', function (string $kunci) {
    Karyawan::factory()->create(['nama' => 'Budi Santoso', 'nik' => '3201010101010001']);
    Karyawan::factory()->create(['nama' => 'Siti Aminah', 'nik' => '3201010101010002']);

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->get(route('karyawans.index', ['search' => $kunci]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('karyawans.data', 1));
})->with(['nama' => 'Budi', 'nik' => '3201010101010002']);

it('mengirim opsi enum ke formulir, bukan daftar yang diketik ulang di layar', function () {
    Jabatan::factory()->create();

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->get(route('karyawans.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('karyawans/create')
            ->has('jabatans')
            ->has('opsi.jenis_kelamin', 2));
});

it('menyimpan karyawan baru', function () {
    $jabatan = Jabatan::factory()->create();

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->post(route('karyawans.store'), kirimanKaryawan($jabatan))
        ->assertRedirect(route('karyawans.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('karyawans', ['nik' => '3201010101010001', 'status' => 'Non Aktif']);
});

it('menyimpan NIK apa adanya, termasuk nol di depannya', function () {
    $jabatan = Jabatan::factory()->create();

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->post(route('karyawans.store'), kirimanKaryawan($jabatan, ['nik' => '0201010101010009']))
        ->assertSessionHasNoErrors();

    expect(Karyawan::first()->nik)->toBe('0201010101010009');
});

it('menolak karyawan dengan masukan tidak sah', function (array $timpa, array $galat) {
    $jabatan = Jabatan::factory()->create();
    Karyawan::factory()->create(['nik' => '3201010101010001']);

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->post(route('karyawans.store'), kirimanKaryawan($jabatan, $timpa))
        ->assertSessionHasErrors($galat);

    expect(Karyawan::count())->toBe(1);
})->with([
    'nama kosong' => [['nama' => ''], ['nama']],
    'NIK sudah dipakai' => [['nik' => '3201010101010001'], ['nik']],
    'jabatan tidak ada' => [['id_jabatan' => 9999], ['id_jabatan']],
    'jenis kelamin di luar enum' => [['jenis_kelamin' => 'X'], ['jenis_kelamin']],
    'tanggal lahir bukan tanggal' => [['tanggal_lahir' => 'kemarin'], ['tanggal_lahir']],
]);

it('B4 — mengabaikan status yang dikirim dari formulir', function () {
    /*
    | Status diturunkan dari penempatan, bukan diketik.
    |
    | Selama masih bisa diisi bebas, menyetelnya "Aktif" dengan tangan membuat
    | pekerja hilang dari daftar pekerja yang tersedia untuk ditempatkan —
    | daftar itu menyaring status Non Aktif — dan tidak ada cara mengembalikannya
    | selain menyunting langsung basis datanya.
    */
    $jabatan = Jabatan::factory()->create();

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->post(route('karyawans.store'), kirimanKaryawan($jabatan, ['status' => 'Aktif']))
        ->assertSessionHasNoErrors();

    expect(Karyawan::firstOrFail()->status)->toBe(StatusKaryawan::NonAktif);

    $karyawan = Karyawan::firstOrFail();

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->put(route('karyawans.update', $karyawan), kirimanKaryawan($jabatan, [
            'nama' => 'Nama Diperbarui',
            'status' => 'Aktif',
        ]))
        ->assertSessionHasNoErrors();

    expect($karyawan->fresh()->status)->toBe(StatusKaryawan::NonAktif);
});

it('membuka formulir ubah karyawan lengkap dengan opsinya', function () {
    $karyawan = Karyawan::factory()->create();

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->get(route('karyawans.edit', $karyawan))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('karyawans/edit')
            ->where('karyawan.id', $karyawan->id)
            ->has('opsi.jenis_kelamin'));
});

it('memperbarui karyawan tanpa mengganti NIK-nya', function () {
    $karyawan = Karyawan::factory()->create(['nik' => '3201010101010001']);

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->put(route('karyawans.update', $karyawan), kirimanKaryawan($karyawan->jabatan, [
            'nama' => 'Nama Diperbarui',
            'nik' => '3201010101010001',
        ]))
        ->assertRedirect(route('karyawans.index'))
        ->assertSessionHasNoErrors();

    expect($karyawan->fresh()->nama)->toBe('Nama Diperbarui');
});

it('menghapus karyawan yang belum pernah masuk penggajian terbayar', function () {
    $karyawan = Karyawan::factory()->create();

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->delete(route('karyawans.destroy', $karyawan))
        ->assertRedirect(route('karyawans.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('karyawans', ['id' => $karyawan->id]);
});

it('menolak penghapusan karyawan yang punya riwayat penggajian terbayar', function () {
    $karyawan = Karyawan::factory()->create();
    $penggajian = Penggajian::factory()->dibayar()->create();

    PenggajianDetail::create([
        'penggajian_id' => $penggajian->id,
        'karyawan_id' => $karyawan->id,
        'gaji_pokok_penuh' => 5_000_000,
        'gaji_pokok' => 5_000_000,
        'bpjs_persen' => 5,
        'bpjs' => 250_000,
        'hari_aktif' => 30,
        'hari_periode' => 30,
        'potongan_cashbon' => 0,
        'total_gaji' => 4_750_000,
    ]);

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->delete(route('karyawans.destroy', $karyawan))
        ->assertRedirect(route('karyawans.index'))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('karyawans', ['id' => $karyawan->id]);
    expect($penggajian->fresh()->status)->toBe(StatusPenggajian::Dibayar);
});

it('E2 — menghapus karyawan yang cashbonnya masih dipesan pada penggajian belum dibayar', function () {
    /*
    | cashbons.karyawan_id memakai cascade, sedangkan cashbon_potongans.cashbon_id
    | memakai restrict. Menghapus karyawan tanpa melepas buku besarnya lebih
    | dahulu menabrak batasan itu dan berakhir sebagai galat SQL mentah — atau,
    | pada urutan cascade yang berbeda, berhasil diam-diam.
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

    Cashbon::factory()->jumlah(2_000_000)->create(['karyawan_id' => $karyawan->id]);

    app(PenggajianService::class)->proses($kontrak);

    expect(CashbonPotongan::count())->toBe(1);

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->delete(route('karyawans.destroy', $karyawan))
        ->assertRedirect(route('karyawans.index'));

    $this->assertDatabaseMissing('karyawans', ['id' => $karyawan->id]);
    expect(CashbonPotongan::count())->toBe(0);

    Carbon::setTestNow();
});

it('menolak setiap aksi karyawan tanpa izin', function (string $metode, string $rute, bool $perluId) {
    $karyawan = Karyawan::factory()->create();

    $this->actingAs(penggunaTanpaIzin())
        ->{$metode}(route($rute, $perluId ? [$karyawan->id] : []))
        ->assertForbidden();
})->with([
    'daftar' => ['get', 'karyawans.index', false],
    'formulir tambah' => ['get', 'karyawans.create', false],
    'simpan' => ['post', 'karyawans.store', false],
    'formulir ubah' => ['get', 'karyawans.edit', true],
    'perbarui' => ['put', 'karyawans.update', true],
    'hapus' => ['delete', 'karyawans.destroy', true],
]);
