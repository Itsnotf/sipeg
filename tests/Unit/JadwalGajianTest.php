<?php

use App\Models\Kontrak;
use App\Support\JadwalGajian;
use Carbon\CarbonImmutable;

function kontrakUji(string $mulai, string $selesai, int $gajian = 25): Kontrak
{
    return new Kontrak([
        'tanggal_mulai' => $mulai,
        'tanggal_selesai' => $selesai,
        'tanggal_gajian' => $gajian,
    ]);
}

it('memecah kontrak setahun menjadi dua belas periode bulanan', function () {
    $periodes = (new JadwalGajian)->untuk(kontrakUji('2026-01-01', '2026-12-31'));

    expect($periodes)->toHaveCount(12)
        ->and($periodes[0]->periode)->toBe('2026-01-01')
        ->and($periodes[11]->periode)->toBe('2026-12-01');
});

it('memotong cakupan periode pertama dan terakhir ke rentang kontrak', function () {
    $periodes = (new JadwalGajian)->untuk(kontrakUji('2026-09-06', '2026-12-20'));

    expect($periodes)->toHaveCount(4);

    // September: mulai ikut tanggal kontrak, bukan tanggal 1
    expect($periodes[0]->mulai->format('Y-m-d'))->toBe('2026-09-06')
        ->and($periodes[0]->selesai->format('Y-m-d'))->toBe('2026-09-30')
        ->and($periodes[0]->final)->toBeFalse();

    // Oktober: bulan penuh
    expect($periodes[1]->mulai->format('Y-m-d'))->toBe('2026-10-01')
        ->and($periodes[1]->selesai->format('Y-m-d'))->toBe('2026-10-31');

    // Desember: selesai ikut tanggal kontrak, bukan akhir bulan
    expect($periodes[3]->mulai->format('Y-m-d'))->toBe('2026-12-01')
        ->and($periodes[3]->selesai->format('Y-m-d'))->toBe('2026-12-20')
        ->and($periodes[3]->final)->toBeTrue();
});

it('memakai jumlah hari bulan kalender sebagai pembagi prorata', function () {
    $periodes = (new JadwalGajian)->untuk(kontrakUji('2026-01-01', '2026-04-30'));

    expect($periodes[0]->hariPeriode)->toBe(31)  // Januari
        ->and($periodes[1]->hariPeriode)->toBe(28)  // Februari 2026, bukan kabisat
        ->and($periodes[2]->hariPeriode)->toBe(31)  // Maret
        ->and($periodes[3]->hariPeriode)->toBe(30); // April
});

it('membayar periode pada tanggal gajian bulan berikutnya', function () {
    $periodes = (new JadwalGajian)->untuk(kontrakUji('2026-01-01', '2026-02-28', 25));

    // Periode Januari dibayar 25 Februari — setelah bulannya selesai
    expect($periodes[0]->tanggalBayar->format('Y-m-d'))->toBe('2026-02-25')
        ->and($periodes[1]->tanggalBayar->format('Y-m-d'))->toBe('2026-03-25');
});

it('menjepit tanggal bayar ke akhir bulan yang lebih pendek', function () {
    $periodes = (new JadwalGajian)->untuk(kontrakUji('2026-01-01', '2026-03-31', 31));

    // Periode Januari dibayar 31 Februari -> dijepit ke 28 Februari 2026
    expect($periodes[0]->tanggalBayar->format('Y-m-d'))->toBe('2026-02-28')
        ->and($periodes[1]->tanggalBayar->format('Y-m-d'))->toBe('2026-03-31')
        ->and($periodes[2]->tanggalBayar->format('Y-m-d'))->toBe('2026-04-30');
});

it('tidak melompati februari saat tanggal gajian 31', function () {
    $periodes = (new JadwalGajian)->untuk(kontrakUji('2026-01-31', '2026-04-30', 31));

    expect(array_map(fn ($p) => $p->periode, $periodes))
        ->toBe(['2026-01-01', '2026-02-01', '2026-03-01', '2026-04-01']);
});

it('menghasilkan satu periode untuk kontrak dalam satu bulan', function () {
    $periodes = (new JadwalGajian)->untuk(kontrakUji('2026-09-06', '2026-09-25'));

    expect($periodes)->toHaveCount(1)
        ->and($periodes[0]->mulai->format('Y-m-d'))->toBe('2026-09-06')
        ->and($periodes[0]->selesai->format('Y-m-d'))->toBe('2026-09-25')
        ->and($periodes[0]->final)->toBeTrue();
});

it('menangani kontrak satu hari', function () {
    $periodes = (new JadwalGajian)->untuk(kontrakUji('2026-09-06', '2026-09-06'));

    expect($periodes)->toHaveCount(1)
        ->and($periodes[0]->hariAktif(null, null))->toBe(1);
});

it('menghitung hari aktif secara inklusif', function () {
    $periode = (new JadwalGajian)->untuk(kontrakUji('2026-09-01', '2026-09-05'))[0];

    // 1 sampai 5 September adalah 5 hari, bukan 4
    expect($periode->hariAktif(null, null))->toBe(5);
});

it('memberi proporsi tepat satu untuk pekerja yang hadir sebulan penuh', function () {
    $periodes = (new JadwalGajian)->untuk(kontrakUji('2026-02-01', '2026-02-28'));
    $februari = $periodes[0];

    expect($februari->hariAktif(null, null))->toBe(28)
        ->and($februari->hariPeriode)->toBe(28)
        ->and($februari->proporsi($februari->hariAktif(null, null)))->toBe(1.0);
});

it('memberi proporsi tepat satu juga pada bulan tiga puluh satu hari', function () {
    $januari = (new JadwalGajian)->untuk(kontrakUji('2026-01-01', '2026-01-31'))[0];

    expect($januari->proporsi($januari->hariAktif(null, null)))->toBe(1.0);
});

it('memberi proporsi separuh untuk pekerja yang masuk di pertengahan bulan', function () {
    $periode = (new JadwalGajian)->untuk(kontrakUji('2026-09-01', '2026-09-30'))[0];

    // Masuk 11 September: 20 dari 30 hari
    expect($periode->proporsi($periode->hariAktif(CarbonImmutable::parse('2026-09-11'), null)))
        ->toBe(20 / 30);
});

it('memotong penempatan ke cakupan periode', function () {
    $periode = (new JadwalGajian)->untuk(kontrakUji('2026-09-01', '2026-09-30'))[0];

    // Penempatan mulai di tengah bulan
    expect($periode->hariAktif(CarbonImmutable::parse('2026-09-11'), null))->toBe(20);

    // Penempatan berakhir di tengah bulan
    expect($periode->hariAktif(null, CarbonImmutable::parse('2026-09-10')))->toBe(10);

    // Penempatan di luar batas periode tetap dijepit
    expect($periode->hariAktif(
        CarbonImmutable::parse('2026-08-01'),
        CarbonImmutable::parse('2026-10-31')
    ))->toBe(30);
});

it('mengembalikan nol hari aktif saat penempatan tidak beririsan', function () {
    $periode = (new JadwalGajian)->untuk(kontrakUji('2026-09-01', '2026-09-30'))[0];

    expect($periode->hariAktif(CarbonImmutable::parse('2026-10-01'), null))->toBe(0)
        ->and($periode->hariAktif(null, CarbonImmutable::parse('2026-08-31')))->toBe(0);
});

it('hanya mengembalikan periode yang sudah jatuh tempo', function () {
    $kontrak = kontrakUji('2026-01-01', '2026-12-31', 25);

    // 1 Maret: hanya periode Januari yang sudah dibayar (25 Februari)
    $jatuhTempo = (new JadwalGajian)->jatuhTempo($kontrak, CarbonImmutable::parse('2026-03-01'));

    expect($jatuhTempo)->toHaveCount(1)
        ->and($jatuhTempo[0]->periode)->toBe('2026-01-01');
});

it('menolak tanggal gajian di luar rentang satu sampai tiga puluh satu', function () {
    (new JadwalGajian)->untuk(kontrakUji('2026-01-01', '2026-12-31', 32));
})->throws(InvalidArgumentException::class);

it('menolak kontrak yang tanggal selesainya mendahului tanggal mulai', function () {
    (new JadwalGajian)->untuk(kontrakUji('2026-12-31', '2026-01-01'));
})->throws(InvalidArgumentException::class);
