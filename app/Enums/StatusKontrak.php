<?php

namespace App\Enums;

enum StatusKontrak: string
{
    case Pending = 'Pending';
    case Progres = 'Progres';
    case Selesai = 'Selesai';

    public function label(): string
    {
        return $this->value;
    }

    /**
     * Kontrak yang penggajiannya boleh diproses.
     *
     * Kontrak selesai tetap ikut: periode terakhirnya baru dibayarkan setelah
     * bulan kerjanya berakhir. Yang ditolak hanyalah kontrak yang belum
     * berjalan — penggajiannya akan langsung terkunci padahal pekerjanya belum
     * tentu benar-benar ditempatkan.
     *
     * Sebelumnya di sini ada aktif() yang tidak pernah dipanggil dari mana pun,
     * sehingga tombol "Proses" di layar dan perintah terjadwal memakai aturan
     * yang berbeda.
     */
    public function bolehDiproses(): bool
    {
        return $this !== self::Pending;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases()
        );
    }
}
