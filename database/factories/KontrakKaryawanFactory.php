<?php

namespace Database\Factories;

use App\Models\Karyawan;
use App\Models\Kontrak;
use App\Models\KontrakKaryawan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KontrakKaryawan>
 */
class KontrakKaryawanFactory extends Factory
{
    protected $model = KontrakKaryawan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kontrak_id' => Kontrak::factory(),
            'karyawan_id' => Karyawan::factory(),
            'tanggal_mulai' => null,
            'tanggal_selesai' => null,
        ];
    }

    public function penempatan(?string $mulai = null, ?string $selesai = null): static
    {
        return $this->state(fn (): array => [
            'tanggal_mulai' => $mulai,
            'tanggal_selesai' => $selesai,
        ]);
    }
}
