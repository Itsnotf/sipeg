<?php

use App\Models\Kontrak;
use App\Models\KontrakDokumen;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Modul dokumen kontrak: unggah, ganti berkas, hapus, dan penjagaan izin.
 *
 * Yang dijaga khusus di sini adalah berkasnya: mengubah nama dokumen tanpa
 * memilih berkas baru tidak boleh menghilangkan lampiran yang sudah ada, dan
 * berkas lama baru dibuang setelah penggantinya tersimpan.
 */
beforeEach(function () {
    Storage::fake('public');

    $this->izin = [
        'kontraks dokumens index', 'kontraks dokumens create',
        'kontraks dokumens edit', 'kontraks dokumens delete',
    ];

    $this->kontrak = Kontrak::factory()->create();
});

it('menampilkan daftar dokumen sebuah kontrak', function () {
    KontrakDokumen::factory()->count(2)->create(['kontrak_id' => $this->kontrak->id]);
    KontrakDokumen::factory()->create();

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->get(route('kontraks.dokumens.index', $this->kontrak->id))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('kontraks/dokumens/index')
            ->has('dokumens.data', 2)
            ->where('kontrak.judul', $this->kontrak->judul));
});

it('mengunggah dokumen baru', function () {
    $this->actingAs(penggunaDenganIzin($this->izin))
        ->post(route('kontraks.dokumens.store', $this->kontrak->id), [
            'nama_dokumen' => 'Surat perjanjian',
            'file' => UploadedFile::fake()->create('perjanjian.pdf', 120, 'application/pdf'),
        ])
        ->assertRedirect(route('kontraks.dokumens.index', $this->kontrak->id))
        ->assertSessionHas('success');

    $dokumen = KontrakDokumen::firstOrFail();

    expect($dokumen->nama_dokumen)->toBe('Surat perjanjian');
    Storage::disk('public')->assertExists($dokumen->file);
});

it('menolak unggahan yang tidak sah', function (array $kiriman, array $galat) {
    $this->actingAs(penggunaDenganIzin($this->izin))
        ->post(route('kontraks.dokumens.store', $this->kontrak->id), $kiriman)
        ->assertSessionHasErrors($galat);

    expect(KontrakDokumen::count())->toBe(0);
})->with([
    'tanpa berkas' => [fn () => ['nama_dokumen' => 'Surat'], ['file']],
    'tanpa nama' => [
        fn () => ['nama_dokumen' => '', 'file' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')],
        ['nama_dokumen'],
    ],
    'jenis berkas terlarang' => [
        fn () => ['nama_dokumen' => 'Surat', 'file' => UploadedFile::fake()->create('skrip.exe', 10)],
        ['file'],
    ],
    'berkas terlalu besar' => [
        fn () => ['nama_dokumen' => 'Surat', 'file' => UploadedFile::fake()->create('besar.pdf', 4096, 'application/pdf')],
        ['file'],
    ],
]);

it('mempertahankan berkas lama saat hanya namanya yang diubah', function () {
    $berkasLama = UploadedFile::fake()->create('lama.pdf', 50, 'application/pdf')
        ->storeAs('dokumens/kontrak-'.$this->kontrak->id, 'lama.pdf', 'public');

    $dokumen = KontrakDokumen::factory()->create([
        'kontrak_id' => $this->kontrak->id,
        'file' => $berkasLama,
    ]);

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->put(route('kontraks.dokumens.update', [$this->kontrak->id, $dokumen->id]), [
            'nama_dokumen' => 'Nama Diperbarui',
        ])
        ->assertRedirect(route('kontraks.dokumens.index', $this->kontrak->id));

    expect($dokumen->fresh()->nama_dokumen)->toBe('Nama Diperbarui')
        ->and($dokumen->fresh()->file)->toBe($berkasLama);

    Storage::disk('public')->assertExists($berkasLama);
});

it('mengganti berkas dan membuang berkas lamanya', function () {
    $berkasLama = UploadedFile::fake()->create('lama.pdf', 50, 'application/pdf')
        ->storeAs('dokumens/kontrak-'.$this->kontrak->id, 'lama.pdf', 'public');

    $dokumen = KontrakDokumen::factory()->create([
        'kontrak_id' => $this->kontrak->id,
        'file' => $berkasLama,
    ]);

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->put(route('kontraks.dokumens.update', [$this->kontrak->id, $dokumen->id]), [
            'nama_dokumen' => $dokumen->nama_dokumen,
            'file' => UploadedFile::fake()->create('baru.pdf', 60, 'application/pdf'),
        ])
        ->assertRedirect(route('kontraks.dokumens.index', $this->kontrak->id));

    $berkasBaru = $dokumen->fresh()->file;

    expect($berkasBaru)->not->toBe($berkasLama);
    Storage::disk('public')->assertExists($berkasBaru);
    Storage::disk('public')->assertMissing($berkasLama);
});

it('menghapus dokumen beserta berkasnya', function () {
    $berkas = UploadedFile::fake()->create('hapus.pdf', 20, 'application/pdf')
        ->storeAs('dokumens/kontrak-'.$this->kontrak->id, 'hapus.pdf', 'public');

    $dokumen = KontrakDokumen::factory()->create([
        'kontrak_id' => $this->kontrak->id,
        'file' => $berkas,
    ]);

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->delete(route('kontraks.dokumens.destroy', [$this->kontrak->id, $dokumen->id]))
        ->assertRedirect(route('kontraks.dokumens.index', $this->kontrak->id));

    $this->assertDatabaseMissing('kontrak_dokumens', ['id' => $dokumen->id]);
    Storage::disk('public')->assertMissing($berkas);
});

it('menolak dokumen milik kontrak lain', function () {
    $dokumenKontrakLain = KontrakDokumen::factory()->create();

    $this->actingAs(penggunaDenganIzin($this->izin))
        ->get(route('kontraks.dokumens.edit', [$this->kontrak->id, $dokumenKontrakLain->id]))
        ->assertNotFound();
});

it('menolak setiap aksi dokumen tanpa izin', function (string $metode, string $rute, bool $perluDokumen) {
    $dokumen = KontrakDokumen::factory()->create(['kontrak_id' => $this->kontrak->id]);

    $parameter = $perluDokumen ? [$this->kontrak->id, $dokumen->id] : [$this->kontrak->id];

    $this->actingAs(penggunaTanpaIzin())
        ->{$metode}(route($rute, $parameter))
        ->assertForbidden();
})->with([
    'daftar' => ['get', 'kontraks.dokumens.index', false],
    'formulir unggah' => ['get', 'kontraks.dokumens.create', false],
    'unggah' => ['post', 'kontraks.dokumens.store', false],
    'formulir ubah' => ['get', 'kontraks.dokumens.edit', true],
    'perbarui' => ['put', 'kontraks.dokumens.update', true],
    'hapus' => ['delete', 'kontraks.dokumens.destroy', true],
]);
