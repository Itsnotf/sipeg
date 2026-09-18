<?php

namespace App\Support;

use App\Exceptions\ExceptionUntukPengguna;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Menentukan pesan mana yang layak ditampilkan kepada pengguna.
 *
 * Sebelumnya controller menulis `'Terjadi kesalahan: '.$e->getMessage()`, yang
 * menangkap SEMUA Throwable — termasuk galat basis data — sehingga teks seperti
 * "SQLSTATE[23000]: Integrity constraint violation" bisa muncul di layar.
 */
final class PesanKesalahan
{
    /**
     * Pesan exception bila memang ditulis untuk pengguna; selain itu pesan
     * umum, dengan rinciannya dicatat ke log agar tetap bisa ditelusuri.
     */
    public static function untukPengguna(Throwable $e, string $bawaan): string
    {
        if ($e instanceof ExceptionUntukPengguna) {
            return $e->getMessage();
        }

        Log::error($bawaan, [
            'exception' => $e::class,
            'message' => $e->getMessage(),
            'file' => $e->getFile().':'.$e->getLine(),
        ]);

        return $bawaan.' Silakan coba lagi, atau hubungi administrator bila berulang.';
    }
}
