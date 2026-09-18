<?php

namespace Database\Factories;

use App\Enums\StatusCashbon;
use App\Models\Cashbon;
use App\Models\Karyawan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cashbon>
 */
class CashbonFactory extends Factory
{
    protected $model = Cashbon::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'karyawan_id' => Karyawan::factory(),
            'jumlah' => fake()->numberBetween(1, 5) * 500_000,
            'keterangan' => fake()->sentence(),
            'status' => StatusCashbon::Berjalan,
        ];
    }

    public function jumlah(float $jumlah): static
    {
        return $this->state(fn (): array => ['jumlah' => $jumlah]);
    }
}
