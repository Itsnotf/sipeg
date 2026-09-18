<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Penjadwalan
|--------------------------------------------------------------------------
|
| Penggajian diproses setiap dini hari untuk periode yang tanggal bayarnya
| sudah tiba. Perintahnya idempoten, sehingga terlewat satu hari tidak
| menimbulkan masalah — periode yang tertinggal akan ikut terproses pada
| jalannya berikutnya.
|
| Penjadwal ini memerlukan `php artisan schedule:work` (saat pengembangan)
| atau satu entri cron ke `schedule:run` (saat dipasang). Antarmuka tetap
| menyediakan tombol proses manual yang memanggil service yang sama.
|
*/

Schedule::command('penggajian:proses')
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->onOneServer();
