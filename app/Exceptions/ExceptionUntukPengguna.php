<?php

namespace App\Exceptions;

/**
 * Penanda bahwa pesan exception ini memang ditulis untuk dibaca pengguna akhir.
 *
 * Tanpa penanda ini, controller tidak punya cara membedakan "Total cashbon
 * berjalan melebihi plafon" — yang wajib dibaca pengguna — dari galat basis data
 * yang tidak boleh bocor ke layar. Sebelumnya keduanya sama-sama ditampilkan apa
 * adanya lewat `'Terjadi kesalahan: '.$e->getMessage()`.
 */
interface ExceptionUntukPengguna {}
