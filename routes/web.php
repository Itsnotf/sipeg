<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\CashbonController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JabatanController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\KontrakController;
use App\Http\Controllers\KontrakDokumenController;
use App\Http\Controllers\KontrakKaryawanController;
use App\Http\Controllers\PenggajianController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);
    Route::resource('karyawans', KaryawanController::class);
    Route::resource('jabatans', JabatanController::class);
    Route::resource('clients', ClientController::class);
    Route::resource('cashbons', CashbonController::class);
    Route::resource('kontraks', KontrakController::class);
    
    // Penggajian - Global list
    Route::get('/penggajians', [PenggajianController::class, 'indexGlobal'])->name('penggajians.index');
    
    // Penggajian - Nested under Kontraks
    Route::prefix('kontraks/{kontrak_id}/dokumens')->group(function () {
        Route::get('/', [KontrakDokumenController::class, 'index'])->name('kontraks.dokumens.index');
        Route::get('/create', [KontrakDokumenController::class, 'create'])->name('kontraks.dokumens.create');
        Route::post('/', [KontrakDokumenController::class, 'store'])->name('kontraks.dokumens.store');
        Route::get('/{dokumen_id}/edit', [KontrakDokumenController::class, 'edit'])->name('kontraks.dokumens.edit');
        Route::put('/{dokumen_id}', [KontrakDokumenController::class, 'update'])->name('kontraks.dokumens.update');
        Route::delete('/{dokumen_id}', [KontrakDokumenController::class, 'destroy'])->name('kontraks.dokumens.destroy');
    });
    Route::prefix('kontraks/{kontrak_id}/karyawans')->group(function () {
        Route::get('/', [KontrakKaryawanController::class, 'index'])->name('kontraks.karyawans.index');
        Route::get('/create', [KontrakKaryawanController::class, 'create'])->name('kontraks.karyawans.create');
        Route::post('/', [KontrakKaryawanController::class, 'store'])->name('kontraks.karyawans.store');
        Route::delete('/{karyawan_id}', [KontrakKaryawanController::class, 'destroy'])->name('kontraks.karyawans.destroy');
    });
    Route::prefix('kontraks/{kontrak_id}/penggajians')->group(function () {
        Route::get('/', [PenggajianController::class, 'index'])->name('kontraks.penggajians.index');
        Route::post('/generate', [PenggajianController::class, 'generate'])->name('kontraks.penggajians.generate');
        Route::get('/{penggajian_id}', [PenggajianController::class, 'show'])->name('kontraks.penggajians.show');
        Route::put('/{penggajian_id}', [PenggajianController::class, 'update'])->name('kontraks.penggajians.update');
    });
});

require __DIR__ . '/settings.php';
