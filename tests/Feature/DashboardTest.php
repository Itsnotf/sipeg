<?php

use App\Models\Karyawan;
use App\Models\Kontrak;
use App\Models\Penggajian;
use App\Models\PenggajianDetail;
use App\Models\User;
use Carbon\CarbonImmutable;

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get(route('dashboard'))->assertOk();
});

it('F1 — menampilkan dua belas periode TERBARU pada grafik komposisi', function () {
    /*
    | take(12) dahulu dijalankan pada koleksi hasil get(), bukan pada kueri —
    | dan urutannya menaik. Grafiknya karena itu membeku pada dua belas periode
    | TERLAMA dan tidak pernah bergerak maju, sekaligus memuat seluruh baris
    | penggajian ke memori setiap kali dashboard dibuka.
    */
    $kontrak = Kontrak::factory()->create();
    $karyawan = Karyawan::factory()->create();

    $mulai = CarbonImmutable::parse('2025-01-01');

    for ($i = 0; $i < 14; $i++) {
        $periode = $mulai->addMonths($i);

        $penggajian = Penggajian::factory()->periode($periode->format('Y-m-d'))->create([
            'kontrak_id' => $kontrak->id,
        ]);

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
    }

    $props = $this->actingAs(penggunaDenganIzin(['penggajians index']))
        ->get(route('dashboard'))
        ->assertOk()
        ->viewData('page')['props'];

    $periodes = array_column($props['komposisi_periode'], 'periode');

    expect($periodes)->toHaveCount(12)
        // Dua periode terlama terpotong, bukan dua yang terbaru.
        ->and($periodes[0])->toBe('2025-03-01')
        ->and(end($periodes))->toBe('2026-02-01');
});

it('D2 — tidak membocorkan angka kontrak dan hutang kepada pengguna tanpa izin', function () {
    /*
    | Dashboard tetap menjadi halaman pendarat bagi siapa pun yang berhasil
    | masuk, tetapi tiap blok angkanya mensyaratkan izin modulnya sendiri.
    | Sebelumnya nilai kontrak, margin per kontrak, sisa hutang, dan nama
    | pekerja terlihat oleh pengguna yang tidak memegang izin apa pun.
    */
    Kontrak::factory()->create(['total_biaya' => 900_000_000]);

    $props = $this->actingAs(penggunaTanpaIzin())
        ->get(route('dashboard'))
        ->assertOk()
        ->viewData('page')['props'];

    expect($props['financial']['total_biaya_kontraks'])->toBe(0.0)
        ->and($props['margin_kontraks'])->toBe([])
        ->and($props['jadwal_terdekat'])->toBe([])
        ->and($props['komposisi_periode'])->toBe([])
        ->and($props['cashbon_status']['sisa'])->toBe(0.0);
});
