<?php

namespace App\Enums;

enum StatusKaryawan: string
{
    case Aktif = 'Aktif';
    case NonAktif = 'Non Aktif';

    public function label(): string
    {
        return $this->value;
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
