<?php

namespace Database\Factories;

use App\Enums\StatusKontrak;
use App\Models\Client;
use App\Models\Kontrak;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kontrak>
 */
class KontrakFactory extends Factory
{
    protected $model = Kontrak::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'judul' => 'Kontrak '.fake()->word(),
            'deskripsi' => fake()->sentence(),
            'tanggal_mulai' => now()->startOfYear()->format('Y-m-d'),
            'tanggal_selesai' => now()->endOfYear()->format('Y-m-d'),
            'tanggal_gajian' => 25,
            'total_biaya' => 500_000_000,
            'status' => StatusKontrak::Progres,
        ];
    }

    public function periode(string $mulai, string $selesai, int $tanggalGajian = 25): static
    {
        return $this->state(fn (): array => [
            'tanggal_mulai' => $mulai,
            'tanggal_selesai' => $selesai,
            'tanggal_gajian' => $tanggalGajian,
        ]);
    }
}
