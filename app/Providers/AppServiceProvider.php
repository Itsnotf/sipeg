<?php

namespace App\Providers;

use App\Support\JadwalGajian;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // JadwalGajian menerima kebijakan pembagi prorata lewat konstruktor
        // agar tetap murni dan bisa diuji tanpa konfigurasi aplikasi.
        $this->app->bind(JadwalGajian::class, fn (): JadwalGajian => new JadwalGajian(
            (bool) config('payroll.hari_per_bulan_kalender', true),
            (int) config('payroll.hari_per_bulan_tetap', 30),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
