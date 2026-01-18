<?php

namespace App\Providers;

use App\Models\Cashbon;
use App\Models\Penggajian;
use App\Observers\CashbonObserver;
use App\Observers\PenggajianObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Cashbon::observe(CashbonObserver::class);
        Penggajian::observe(PenggajianObserver::class);
    }
}
