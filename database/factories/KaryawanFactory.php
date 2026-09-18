<?php

namespace Database\Factories;

use App\Enums\JenisKelamin;
use App\Enums\StatusKaryawan;
use App\Models\Jabatan;
use App\Models\Karyawan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Karyawan>
 */
class KaryawanFactory extends Factory
{
    protected $model = Karyawan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_jabatan' => Jabatan::factory(),
            'nama' => fake()->name(),
            'nik' => fake()->unique()->numerify('################'),
            'alamat' => fake()->address(),
            'jenis_kelamin' => fake()->randomElement(JenisKelamin::cases()),
            'tanggal_lahir' => fake()->date('Y-m-d', '2000-01-01'),
            'no_hp' => fake()->numerify('08##########'),
            'status' => StatusKaryawan::NonAktif,
        ];
    }

    public function aktif(): static
    {
        return $this->state(fn (): array => ['status' => StatusKaryawan::Aktif]);
    }
}
