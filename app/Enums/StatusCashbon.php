<?php

namespace App\Enums;

enum StatusCashbon: string
{
    case Berjalan = 'berjalan';
    case Lunas = 'lunas';

    public function label(): string
    {
        return match ($this) {
            self::Berjalan => 'Berjalan',
            self::Lunas => 'Lunas',
        };
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
