<?php

namespace App\Observers;

use App\Models\Cashbon;
use App\Models\Penggajian;
use App\Models\PenggajianDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashbonObserver
{
    /**
     * Handle the Cashbon "created" event.
     */
    public function created(Cashbon $cashbon): void
    {
        // Saat cashbon baru dibuat dengan status belum_dibayar,
        // otomatis potong di penggajian terdekat yang belum dibayar
        if ($cashbon->status === 'belum_dibayar') {
            $this->applyToPenggajian($cashbon);
        }
    }

    /**
     * Handle the Cashbon "updated" event.
     */
    public function updated(Cashbon $cashbon): void
    {
        // Jika status berubah dari belum_dibayar ke dibayar atau lainnya
        if ($cashbon->isDirty('status')) {
            $oldStatus = $cashbon->getOriginal('status');
            
            // Jika berubah menjadi dibayar, remove potongan dari penggajian
            if ($oldStatus === 'belum_dibayar' && $cashbon->status === 'dibayar') {
                $this->removeFromPenggajian($cashbon);
            }
            // Jika berubah menjadi belum_dibayar lagi, apply potongan
            elseif ($oldStatus !== 'belum_dibayar' && $cashbon->status === 'belum_dibayar') {
                $this->applyToPenggajian($cashbon);
            }
        }
    }

    /**
     * Handle the Cashbon "deleted" event.
     */
    public function deleted(Cashbon $cashbon): void
    {
        // Jika cashbon dihapus, hapus potongannya dari penggajian
        $this->removeFromPenggajian($cashbon);
    }

    /**
     * Apply cashbon to nearest pending penggajian
     */
    private function applyToPenggajian(Cashbon $cashbon): void
    {
        try {
            DB::transaction(function () use ($cashbon) {
                // Cari kontrak yang terkait dengan karyawan ini
                $kontrakIds = $cashbon->karyawan->kontrakKaryawans()
                    ->pluck('kontrak_id')
                    ->toArray();

                if (empty($kontrakIds)) {
                    return;
                }

                // Cari penggajian terdekat dengan status belum_dibayar
                $penggajian = Penggajian::whereIn('kontrak_id', $kontrakIds)
                    ->where('status', 'belum_dibayar')
                    ->where('periode', '>=', now()->startOfMonth())
                    ->orderBy('periode', 'asc')
                    ->first();

                if (!$penggajian) {
                    return;
                }

                // Cari atau buat penggajian_detail untuk karyawan ini di penggajian tersebut
                $penggajianDetail = PenggajianDetail::firstOrCreate(
                    [
                        'penggajian_id' => $penggajian->id,
                        'karyawan_id' => $cashbon->karyawan_id,
                    ],
                    [
                        'gaji_pokok' => $cashbon->karyawan->jabatan?->gaji ?? 0,
                        'bpjs' => $cashbon->karyawan->jabatan?->bpjs ?? 0,
                        'potongan_cashbon' => 0,
                        'total_gaji' => 0,
                    ]
                );

                // Update potongan_cashbon
                $newPotongan = $penggajianDetail->potongan_cashbon + (float) $cashbon->jumlah;
                $penggajianDetail->update([
                    'potongan_cashbon' => $newPotongan,
                    'total_gaji' => max(0, $penggajianDetail->gaji_pokok - $penggajianDetail->bpjs - $newPotongan),
                ]);

                // Update total_gaji penggajian
                $this->updatePenggajianTotal($penggajian);
            });
        } catch (\Exception $e) {
            Log::error('Error applying cashbon to penggajian: ' . $e->getMessage());
        }
    }

    /**
     * Remove cashbon from penggajian
     */
    private function removeFromPenggajian(Cashbon $cashbon): void
    {
        try {
            DB::transaction(function () use ($cashbon) {
                // Cari penggajian_details yang punya potongan dari cashbon ini
                $details = PenggajianDetail::where('karyawan_id', $cashbon->karyawan_id)
                    ->whereHas('penggajian', function ($q) {
                        $q->where('status', 'belum_dibayar');
                    })
                    ->get();

                foreach ($details as $detail) {
                    // Kurangi potongan_cashbon
                    $newPotongan = max(0, $detail->potongan_cashbon - (float) $cashbon->jumlah);
                    $detail->update([
                        'potongan_cashbon' => $newPotongan,
                        'total_gaji' => max(0, $detail->gaji_pokok - $detail->bpjs - $newPotongan),
                    ]);

                    // Update total penggajian
                    $this->updatePenggajianTotal($detail->penggajian);
                }
            });
        } catch (\Exception $e) {
            Log::error('Error removing cashbon from penggajian: ' . $e->getMessage());
        }
    }

    /**
     * Update total gaji penggajian
     */
    private function updatePenggajianTotal(Penggajian $penggajian): void
    {
        $total = $penggajian->penggajianDetails()
            ->sum('total_gaji');

        $penggajian->update([
            'total_gaji' => $total,
        ]);
    }
}
