<?php

namespace Database\Factories;

use App\Models\Kontrak;
use App\Models\KontrakDokumen;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KontrakDokumen>
 */
class KontrakDokumenFactory extends Factory
{
    protected $model = KontrakDokumen::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kontrak_id' => Kontrak::factory(),
            'nama_dokumen' => 'Dokumen '.fake()->words(2, true),
            'file' => 'dokumens/kontrak-1/'.fake()->uuid().'.pdf',
        ];
    }
}
