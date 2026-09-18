<?php

use App\Models\Cashbon;
use App\Models\Client;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\Kontrak;
use App\Models\KontrakDokumen;
use App\Models\KontrakKaryawan;
use App\Services\PenggajianService;
use Carbon\Carbon;

/**
 * Tidak satu pun prop Inertia boleh membawa stempel waktu ISO mentah.
 *
 * Inilah penjaga bug yang dilaporkan: "2026-03-31T17:00:00.000000Z" adalah
 * pukul 00:00 WIB tanggal 1 April, hasil Carbon yang diserialisasi ke UTC.
 * Penyebabnya, cast 'date:Y-m-d' hanya berlaku ketika model diserialisasi utuh
 * lewat toArray(); begitu atributnya diambil satu per satu ke array rakitan
 * tangan, cast itu terlewat. Berkas ini menyisir seluruh halaman dan menolak
 * pola jam pada nilai prop mana pun.
 */
beforeEach(function () {
    Carbon::setTestNow('2026-04-15');

    $izin = [
        'users index', 'roles index', 'jabatans index', 'jabatans create', 'jabatans edit',
        'karyawans index', 'karyawans create', 'karyawans edit',
        'clients index', 'clients create', 'clients edit',
        'cashbons index', 'cashbons create', 'cashbons edit',
        'kontraks index', 'kontraks create', 'kontraks edit',
        'kontraks dokumens index', 'kontraks dokumens create', 'kontraks dokumens edit',
        'kontraks karyawans index', 'kontraks karyawans create',
        'penggajians index', 'penggajians show',
    ];

    $this->actingAs(penggunaDenganIzin($izin));

    $jabatan = Jabatan::factory()->gaji(6_000_000, 5)->create();
    $this->karyawan = Karyawan::factory()->aktif()->create(['id_jabatan' => $jabatan->id]);
    $this->client = Client::factory()->create();
    $this->kontrak = Kontrak::factory()->periode('2026-01-01', '2026-12-31', 25)->create([
        'client_id' => $this->client->id,
    ]);

    KontrakKaryawan::factory()->create([
        'kontrak_id' => $this->kontrak->id,
        'karyawan_id' => $this->karyawan->id,
        'tanggal_mulai' => '2026-01-01',
    ]);

    $this->cashbon = Cashbon::factory()->jumlah(2_000_000)->create(['karyawan_id' => $this->karyawan->id]);
    $this->dokumen = KontrakDokumen::factory()->create(['kontrak_id' => $this->kontrak->id]);

    app(PenggajianService::class)->proses($this->kontrak);

    $this->penggajian = $this->kontrak->penggajians()->firstOrFail();
});

afterEach(function () {
    Carbon::setTestNow();
});

/**
 * Menelusuri seluruh prop dan mengembalikan jalur nilai yang berpola jam.
 *
 * @param  mixed  $nilai
 * @return array<int, string>
 */
function jejakStempelWaktu($nilai, string $jalur = ''): array
{
    if (is_array($nilai)) {
        $temuan = [];

        foreach ($nilai as $kunci => $isi) {
            $temuan = [...$temuan, ...jejakStempelWaktu($isi, $jalur === '' ? (string) $kunci : "{$jalur}.{$kunci}")];
        }

        return $temuan;
    }

    if (is_string($nilai) && preg_match('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $nilai) === 1) {
        return ["{$jalur} = {$nilai}"];
    }

    return [];
}

it('tidak mengirim satu pun stempel waktu ISO ke halaman mana pun', function (string $rute) {
    $response = $this->get($rute);

    expect($response->status())->toBeIn([200, 302]);

    if ($response->status() !== 200) {
        return;
    }

    $props = $response->viewData('page')['props'] ?? [];

    expect(jejakStempelWaktu($props))->toBe([]);
})->with(fn () => [
    'dashboard' => '/dashboard',
    'daftar pengguna' => '/users',
    'daftar role' => '/roles',
    'daftar jabatan' => '/jabatans',
    'tambah jabatan' => '/jabatans/create',
    'daftar karyawan' => '/karyawans',
    'tambah karyawan' => '/karyawans/create',
    'daftar client' => '/clients',
    'tambah client' => '/clients/create',
    'daftar cashbon' => '/cashbons',
    'buat cashbon' => '/cashbons/create',
    'daftar kontrak' => '/kontraks',
    'tambah kontrak' => '/kontraks/create',
    'daftar penggajian' => '/penggajians',
]);

it('tidak mengirim stempel waktu ISO pada halaman bergantung data', function (string $nama) {
    $rute = match ($nama) {
        'detail kontrak' => route('kontraks.show', $this->kontrak->id),
        'ubah kontrak' => route('kontraks.edit', $this->kontrak->id),
        'ubah karyawan' => route('karyawans.edit', $this->karyawan->id),
        'ubah client' => route('clients.edit', $this->client->id),
        'ubah cashbon' => route('cashbons.edit', $this->cashbon->id),
        'dokumen kontrak' => route('kontraks.dokumens.index', $this->kontrak->id),
        'ubah dokumen' => route('kontraks.dokumens.edit', [$this->kontrak->id, $this->dokumen->id]),
        'penempatan kontrak' => route('kontraks.karyawans.index', $this->kontrak->id),
        'tambah penempatan' => route('kontraks.karyawans.create', $this->kontrak->id),
        'penggajian kontrak' => route('kontraks.penggajians.index', $this->kontrak->id),
        'detail penggajian' => route('kontraks.penggajians.show', [$this->kontrak->id, $this->penggajian->id]),
    };

    $response = $this->get($rute)->assertOk();

    expect(jejakStempelWaktu($response->viewData('page')['props']))->toBe([]);
})->with([
    'detail kontrak',
    'ubah kontrak',
    'ubah karyawan',
    'ubah client',
    'ubah cashbon',
    'dokumen kontrak',
    'ubah dokumen',
    'penempatan kontrak',
    'tambah penempatan',
    'penggajian kontrak',
    'detail penggajian',
]);

it('mengirim tanggal kontrak sebagai Y-m-d, bukan ISO dengan zona waktu', function () {
    $response = $this->get(route('kontraks.show', $this->kontrak->id))->assertOk();

    $kontrak = $response->viewData('page')['props']['kontrak'];

    expect($kontrak['tanggal_mulai'])->toBe('2026-01-01')
        ->and($kontrak['tanggal_selesai'])->toBe('2026-12-31');
});

it('tidak menyertakan stempel waktu izin pada prop bersama', function () {
    $props = $this->get('/dashboard')->assertOk()->viewData('page')['props'];

    expect($props['auth']['permissions'][0])->toHaveKeys(['id', 'name'])
        ->and($props['auth']['permissions'][0])->not->toHaveKey('created_at')
        ->and($props['auth']['user'])->not->toHaveKey('created_at');
});
