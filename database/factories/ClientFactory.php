<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_client' => fake()->company(),
            'alamat' => fake()->address(),
            'email' => fake()->unique()->companyEmail(),
            'no_hp' => fake()->numerify('08##########'),
            'deskripsi' => fake()->sentence(),
        ];
    }
}
