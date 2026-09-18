<?php

namespace Database\Factories;

use App\Models\Jabatan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Jabatan>
 */
class JabatanFactory extends Factory
{
    protected $model = Jabatan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gaji = fake()->numberBetween(5, 20) * 1_000_000;

        return [
            'nama_jabatan' => fake()->unique()->jobTitle(),
            'deskripsi' => fake()->sentence(),
            'gaji' => $gaji,
            'bpjs' => $gaji * 0.05,
            'bpjs_persen' => 5.0,
        ];
    }

    public function gaji(float $gaji, float $bpjsPersen = 5.0): static
    {
        return $this->state(fn (): array => [
            'gaji' => $gaji,
            'bpjs' => $gaji * $bpjsPersen / 100,
            'bpjs_persen' => $bpjsPersen,
        ]);
    }
}
