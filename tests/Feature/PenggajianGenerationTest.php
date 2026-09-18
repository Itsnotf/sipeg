<?php

use App\Enums\StatusPenggajian;
use App\Models\Cashbon;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\Kontrak;
use App\Models\KontrakKaryawan;
use App\Models\Penggajian;
use App\Models\User;
use Carbon\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Pemrosesan penggajian lewat rute dan controller sungguhan.
 *
 * Perhitungan mesinnya sendiri diuji di PenggajianProsesTest pada tingkat
 * service. Berkas ini menguji lapisan di atasnya: tombol proses, penandaan
 * pembayaran, penguncian transisi, dan penjagaan izin.
 */
beforeEach(function () {
    Carbon::setTestNow('2026-04-01');

    foreach (['penggajians index', 'penggajians show', 'penggajians generate', 'penggajians update'] as $izin) {
        Permission::findOrCreate($izin);
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->user = User::factory()->create();
    $this->user->givePermissionTo([
        'penggajians index', 'penggajians show', 'penggajians generate', 'penggajians update',
    ]);
    $this->actingAs($this->user);

    $jabatan = Jabatan::factory()->gaji(10_000_000, 5.0)->create();

    $this->karyawan = Karyawan::factory()->create(['id_jabatan' => $jabatan->id]);

    $this->kontrak = Kontrak::factory()->periode('2026-01-01', '2026-06-30', 25)->create();

    KontrakKaryawan::factory()->create([
        'kontrak_id' => $this->kontrak->id,
        'karyawan_id' => $this->karyawan->id,
        'tanggal_mulai' => '2026-01-01',
    ]);
});

afterEach(function () {
    Carbon::setTestNow();
});

it('memproses periode jatuh tempo lewat tombol proses', function () {
    $this->post(route('kontraks.penggajians.generate', $this->kontrak->id))
        ->assertRedirect(route('kontraks.penggajians.index', $this->kontrak->id))
        ->assertSessionHas('success');

    expect(Penggajian::where('kontrak_id', $this->kontrak->id)->count())->toBe(2);
});

it('memberi tahu saat tidak ada periode baru yang jatuh tempo', function () {
    $this->post(route('kontraks.penggajians.generate', $this->kontrak->id));

    $this->post(route('kontraks.penggajians.generate', $this->kontrak->id))
        ->assertSessionHas('info');

    expect(Penggajian::where('kontrak_id', $this->kontrak->id)->count())->toBe(2);
});

it('menandai penggajian sudah dibayar lewat formulir', function () {
    $this->post(route('kontraks.penggajians.generate', $this->kontrak->id));

    $penggajian = Penggajian::where('periode', '2026-01-01')->firstOrFail();

    $this->put(
        route('kontraks.penggajians.update', [$this->kontrak->id, $penggajian->id]),
        ['status' => 'dibayar']
    )->assertRedirect(route('kontraks.penggajians.show', [$this->kontrak->id, $penggajian->id]));

    expect($penggajian->fresh()->status)->toBe(StatusPenggajian::Dibayar);
});

it('menolak pembatalan pembayaran', function () {
    $this->post(route('kontraks.penggajians.generate', $this->kontrak->id));

    $penggajian = Penggajian::where('periode', '2026-01-01')->firstOrFail();

    $this->put(
        route('kontraks.penggajians.update', [$this->kontrak->id, $penggajian->id]),
        ['status' => 'dibayar']
    );

    // Transisi hanya searah — uang yang sudah berpindah tidak bisa ditarik
    // kembali dengan mengubah status
    $this->put(
        route('kontraks.penggajians.update', [$this->kontrak->id, $penggajian->id]),
        ['status' => 'belum_dibayar']
    )->assertSessionHasErrors('status');

    expect($penggajian->fresh()->status)->toBe(StatusPenggajian::Dibayar);
});

it('menolak membayar ulang penggajian yang sudah dibayar', function () {
    $this->post(route('kontraks.penggajians.generate', $this->kontrak->id));

    $penggajian = Penggajian::where('periode', '2026-01-01')->firstOrFail();
    $rute = route('kontraks.penggajians.update', [$this->kontrak->id, $penggajian->id]);

    $this->put($rute, ['status' => 'dibayar']);
    $this->put($rute, ['status' => 'dibayar'])->assertSessionHas('error');
});

it('menampilkan rincian asal setiap potongan pada halaman detail', function () {
    Cashbon::factory()->jumlah(3_000_000)->create(['karyawan_id' => $this->karyawan->id]);

    $this->post(route('kontraks.penggajians.generate', $this->kontrak->id));

    $penggajian = Penggajian::where('periode', '2026-01-01')->firstOrFail();

    $this->get(route('kontraks.penggajians.show', [$this->kontrak->id, $penggajian->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('kontraks/penggajians/show')
            ->where('penggajian.penggajianDetails.0.potongan_cashbon', 3_000_000)
            ->where('penggajian.penggajianDetails.0.hari_aktif', 31)
            ->has('penggajian.penggajianDetails.0.potongans', 1)
        );
});

it('menyertakan jadwal periode mendatang pada halaman daftar', function () {
    $this->post(route('kontraks.penggajians.generate', $this->kontrak->id));

    $this->get(route('kontraks.penggajians.index', $this->kontrak->id))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('kontraks/penggajians/index')
            // Enam periode seluruhnya, dua sudah dibuat, empat masih terjadwal
            ->has('jadwal', 4)
        );
});

it('menolak pengguna tanpa izin memproses penggajian', function () {
    $orangLain = User::factory()->create();

    $this->actingAs($orangLain)
        ->post(route('kontraks.penggajians.generate', $this->kontrak->id))
        ->assertForbidden();

    expect(Penggajian::count())->toBe(0);
});
