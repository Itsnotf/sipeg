<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Aturan bisnis yang dilanggar, dengan pesan yang memang untuk dibaca pengguna.
 *
 * Dipakai lapisan service ketika permintaan sah secara teknis tetapi ditolak
 * oleh aturan domain — plafon terlampaui, jendela koreksi lewat, penggajian
 * sudah terkunci.
 */
class KesalahanAturan extends RuntimeException implements ExceptionUntukPengguna {}
