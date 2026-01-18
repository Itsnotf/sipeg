<?php

namespace App\Observers;

use App\Models\Penggajian;
use App\Models\Cashbon;
use Illuminate\Support\Facades\DB;

class PenggajianObserver
{
    /**
     * Handle the Penggajian "updated" event.
     * 
     * Ketika status penggajian berubah menjadi 'dibayar',
     * semua cashbon yang sudah dipotong juga harus berubah menjadi 'dibayar'
     */
    public function updated(Penggajian $penggajian): void
    {
        // Check if status changed to 'dibayar'
        if ($penggajian->isDirty('status') && $penggajian->status === 'dibayar') {
            $this->markCashbonsAsPaid($penggajian);
        }
    }

    /**
     * Mark cashbons as paid for this penggajian
     */
    private function markCashbonsAsPaid(Penggajian $penggajian): void
    {
        try {
            DB::transaction(function () use ($penggajian) {
                // Get all penggajian details yang punya potongan cashbon
                $penggajianDetails = $penggajian->penggajianDetails()
                    ->where('potongan_cashbon', '>', 0)
                    ->get();

                foreach ($penggajianDetails as $detail) {
                    // Find cashbons untuk karyawan ini yang status belum_dibayar
                    $cashbons = Cashbon::where('karyawan_id', $detail->karyawan_id)
                        ->where('status', 'belum_dibayar')
                        ->get();

                    foreach ($cashbons as $cashbon) {
                        // Only mark as paid if the amount matches or is covered
                        // Mark the cashbon that matches the deduction amount
                        if ((float) $cashbon->jumlah <= $detail->potongan_cashbon) {
                            $cashbon->update(['status' => 'dibayar']);
                            \Log::info('Cashbon marked as dibayar', [
                                'cashbon_id' => $cashbon->id,
                                'karyawan_id' => $cashbon->karyawan_id,
                                'jumlah' => $cashbon->jumlah,
                                'penggajian_id' => $penggajian->id,
                            ]);
                        }
                    }
                }
            });
        } catch (\Exception $e) {
            \Log::error('Error marking cashbons as paid: ' . $e->getMessage());
        }
    }
}
