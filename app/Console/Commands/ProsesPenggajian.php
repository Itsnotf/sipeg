<?php

namespace App\Console\Commands;

use App\Enums\StatusKontrak;
use App\Models\Kontrak;
use App\Services\PenggajianService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Membuat penggajian untuk setiap periode yang tanggal bayarnya sudah tiba.
 *
 * Dijadwalkan harian, tetapi aman dijalankan kapan saja dan berapa kali pun —
 * periode yang sudah ada dilewati. Tombol manual di antarmuka memanggil service
 * yang sama, sehingga demo tidak bergantung pada penjadwal yang berjalan.
 */
class ProsesPenggajian extends Command
{
    protected $signature = 'penggajian:proses
                            {--kontrak= : Batasi pada satu kontrak saja}';

    protected $description = 'Memproses penggajian untuk periode yang sudah jatuh tempo';

    public function handle(PenggajianService $service): int
    {
        $kontraks = Kontrak::query()
            ->when(
                $this->option('kontrak'),
                fn ($query, $id) => $query->where('id', $id),
                // Kontrak yang sudah selesai tetap diproses: periode terakhirnya
                // baru dibayarkan setelah bulan kerjanya berakhir.
                fn ($query) => $query->whereIn('status', [StatusKontrak::Progres, StatusKontrak::Selesai])
            )
            ->get();

        if ($kontraks->isEmpty()) {
            $this->info('Tidak ada kontrak yang perlu diproses.');

            return self::SUCCESS;
        }

        $totalPeriode = 0;
        $gagal = 0;

        foreach ($kontraks as $kontrak) {
            try {
                $dibuat = $service->proses($kontrak);
                $totalPeriode += count($dibuat);

                if ($dibuat !== []) {
                    $this->line(sprintf(
                        '  %s: %d periode diproses.',
                        $kontrak->judul,
                        count($dibuat)
                    ));
                }
            } catch (Throwable $e) {
                $gagal++;
                $this->error(sprintf('  %s: %s', $kontrak->judul, $e->getMessage()));
            }
        }

        $this->info(sprintf(
            'Selesai. %d periode dibuat dari %d kontrak%s.',
            $totalPeriode,
            $kontraks->count(),
            $gagal > 0 ? ", {$gagal} kontrak gagal" : ''
        ));

        return $gagal > 0 ? self::FAILURE : self::SUCCESS;
    }
}
