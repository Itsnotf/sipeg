<?php

use App\Models\Kontrak;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

/**
 * Galat HTTP harus tetap berada di dalam aplikasi.
 *
 * Sebelum ini `withExceptions` kosong, sehingga 403 dan 404 mengeluarkan
 * pengguna ke halaman bawaan Laravel: tanpa navigasi, tanpa identitas, dan
 * tanpa jalan kembali selain tombol back peramban.
 */
it('menggambar 404 sebagai halaman Inertia', function () {
    $this->actingAs(penggunaTanpaIzin())
        ->get('/alamat-yang-tidak-ada')
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page->component('errors/index')->where('status', 404));
});

it('menggambar 404 untuk sumber daya yang tidak ditemukan', function () {
    $this->actingAs(penggunaDenganIzin(['kontraks index']))
        ->get(route('kontraks.show', 9999))
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page->component('errors/index')->where('status', 404));
});

it('menggambar 403 sebagai halaman Inertia', function () {
    Kontrak::factory()->create();

    $this->actingAs(penggunaTanpaIzin())
        ->get(route('kontraks.index'))
        ->assertForbidden()
        ->assertInertia(fn ($page) => $page->component('errors/index')->where('status', 403));
});

it('menjawab JSON, bukan halaman, untuk permintaan JSON', function () {
    $this->actingAs(penggunaTanpaIzin())
        ->getJson(route('kontraks.index'))
        ->assertForbidden()
        ->assertHeader('content-type', 'application/json');
});

it('mengarahkan tamu ke halaman masuk alih-alih menampilkan galat', function () {
    $this->get(route('kontraks.index'))->assertRedirect(route('login'));
});

it('memulangkan pengguna dengan pesan saat sesinya berakhir', function () {
    // 419 adalah jawaban Laravel untuk token CSRF tidak sah — mis. tab yang
    // dibiarkan terbuka semalaman lalu formulirnya dikirim keesokan harinya.
    // Middleware CSRF dimatikan di lingkungan pengujian, jadi exception-nya
    // dilewatkan langsung ke penangan yang sesungguhnya.
    $this->actingAs(penggunaDenganIzin(['clients index']));

    $request = Request::create(route('clients.store'), 'POST');
    $request->setLaravelSession(app('session.store'));

    $response = app(ExceptionHandler::class)->render($request, new TokenMismatchException);

    expect($response->getStatusCode())->toBe(302);
    expect(session('error'))->toBe('Sesi Anda telah berakhir. Silakan coba lagi.');
});
