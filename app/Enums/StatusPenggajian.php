<?php

namespace App\Enums;

enum StatusPenggajian: string
{
    case BelumDibayar = 'belum_dibayar';
    case Dibayar = 'dibayar';

    public function label(): string
    {
        return match ($this) {
            self::BelumDibayar => 'Belum Dibayar',
            self::Dibayar => 'Dibayar',
        };
    }

    /**
     * Penggajian yang sudah dibayar bersifat immutable — nominalnya tidak
     * boleh dihitung ulang dan alokasi cashbonnya tidak boleh dilepas.
     */
    public function terkunci(): bool
    {
        return $this === self::Dibayar;
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
