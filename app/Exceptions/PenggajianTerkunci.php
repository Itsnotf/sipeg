<?php

namespace App\Exceptions;

/**
 * Dilempar saat ada usaha mengubah penggajian yang sudah dibayar.
 *
 * Penggajian terbayar bersifat immutable: nominalnya tidak boleh dihitung ulang
 * dan alokasi cashbonnya tidak boleh dilepas, karena uangnya sudah berpindah.
 */
class PenggajianTerkunci extends KesalahanAturan
{
    public static function untukPeriode(string $periode): self
    {
        return new self(
            "Penggajian periode {$periode} sudah dibayar dan tidak dapat diubah lagi."
        );
    }
}
