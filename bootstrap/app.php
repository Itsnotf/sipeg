<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
        | Galat HTTP digambar sebagai halaman Inertia bergaya aplikasi.
        |
        | Sebelumnya 403 dan 404 mengeluarkan pengguna dari aplikasi ke halaman
        | bawaan Laravel — tanpa navigasi, tanpa identitas, dan tanpa jalan
        | kembali selain tombol back peramban.
        */
        $exceptions->respond(function (Response $response, Throwable $e, Request $request): Response {
            $status = $response->getStatusCode();

            // Sesi kedaluwarsa bukan halaman buntu: kembalikan pengguna ke
            // formulirnya dengan pesan, bukan ke layar galat.
            if ($status === 419) {
                return back()->with('error', 'Sesi Anda telah berakhir. Silakan coba lagi.');
            }

            // Saat debug menyala, jejak galat server jauh lebih berguna bagi
            // pengembang daripada halaman yang rapi.
            $gambarHalaman = in_array($status, [403, 404], true)
                || (in_array($status, [500, 503], true) && ! config('app.debug'));

            if ($gambarHalaman && ! $request->expectsJson()) {
                return Inertia::render('errors/index', ['status' => $status])
                    ->toResponse($request)
                    ->setStatusCode($status);
            }

            return $response;
        });
    })->create();
