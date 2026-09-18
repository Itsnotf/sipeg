<?php

use App\Models\User;

/*
| Akar situs mengarahkan ke halaman masuk.
|
| Dahulu di sini ada halaman sambutan yang dijawab 200. SIPEG adalah aplikasi
| internal — tidak ada yang bisa dikerjakan tanpa akun — sehingga halaman itu
| hanya menjadi satu ketukan tambahan sebelum masuk.
*/
it('mengarahkan tamu dari akar situs ke halaman masuk', function () {
    $this->get('/')->assertRedirect('/login');
});

it('tidak menahan pengguna yang sudah masuk di halaman masuk', function () {
    $this->actingAs(User::factory()->create());

    // Middleware tamu pada rute login memantulkannya ke dashboard, sehingga
    // akar situs tetap menjadi jalan masuk yang benar bagi keduanya.
    $this->get('/')->assertRedirect('/login');
    $this->get('/login')->assertRedirect(route('dashboard'));
});
