<?php

namespace Database\Factories;

use App\Enums\StatusPenggajian;
use App\Models\Kontrak;
use App\Models\Penggajian;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Penggajian>
 */
class PenggajianFactory extends Factory
{
    protected $model = Penggajian::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $periode = Carbon::now()->startOfMonth();

        return [
            'kontrak_id' => Kontrak::factory(),
            'periode' => $periode->format('Y-m-d'),
            'periode_mulai' => $periode->format('Y-m-d'),
            'periode_selesai' => $periode->copy()->endOfMonth()->format('Y-m-d'),
            'tanggal_bayar' => null,
            'final' => false,
            'status' => StatusPenggajian::BelumDibayar,
            'total_gaji' => 0,
        ];
    }

    /** Periode tertentu, berikut rentang bulan kalendernya. */
    public function periode(string $periode): static
    {
        $mulai = Carbon::parse($periode)->startOfMonth();

        return $this->state(fn (): array => [
            'periode' => $mulai->format('Y-m-d'),
            'periode_mulai' => $mulai->format('Y-m-d'),
            'periode_selesai' => $mulai->copy()->endOfMonth()->format('Y-m-d'),
        ]);
    }

    /** Penggajian yang sudah dibayar — terkunci dan tidak boleh dihitung ulang. */
    public function dibayar(?string $tanggalBayar = null): static
    {
        return $this->state(fn (array $atribut): array => [
            'status' => StatusPenggajian::Dibayar,
            'final' => true,
            'tanggal_bayar' => $tanggalBayar ?? Carbon::parse($atribut['periode_selesai'])->format('Y-m-d'),
        ]);
    }
}
