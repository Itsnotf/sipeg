<?php

namespace App\Services;

use App\Models\Kontrak;
use App\Models\Penggajian;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PenggajianService
{
    /**
     * Generate penggajian untuk sebuah kontrak
     * 
     * @param Kontrak $kontrak
     * @return array
     * @throws \Exception
     */
    public function generate(Kontrak $kontrak): array
    {
        $this->validateKontrak($kontrak);

        return DB::transaction(function () use ($kontrak) {
            // Extract day from tanggal_gajian
            $tanggalGajianValue = $kontrak->tanggal_gajian;
            
            if (strlen($tanggalGajianValue) > 2) {
                $tanggalGajian = (int) date('d', strtotime($tanggalGajianValue));
            } else {
                $tanggalGajian = (int) $tanggalGajianValue;
            }
            
            $tanggalMulai = Carbon::parse($kontrak->tanggal_mulai);
            $tanggalSelesai = Carbon::parse($kontrak->tanggal_selesai);

            // Generate semua tanggal gajian dalam rentang kontrak
            $periodes = $this->generatePeriodes($tanggalMulai, $tanggalSelesai, $tanggalGajian);

            if (empty($periodes)) {
                throw new \Exception('Tidak ada periode gajian yang valid dalam rentang waktu kontrak.');
            }

            $penggajianRecords = [];
            
            // Check if kontrakKaryawans is already loaded from controller
            // If not, load it fresh
            if (!$kontrak->relationLoaded('kontrakKaryawans')) {
                $kontrak->load('kontrakKaryawans.karyawan.jabatan', 'kontrakKaryawans.karyawan.cashbons');
            }
            
            $karyawans = $kontrak->kontrakKaryawans;

            if ($karyawans->isEmpty()) {
                throw new \Exception('Kontrak tidak memiliki karyawan yang terdaftar.');
            }

            // Create penggajian untuk setiap periode
            foreach ($periodes as $periode) {
                $existingPenggajian = Penggajian::where('kontrak_id', $kontrak->id)
                    ->where('periode', $periode)
                    ->first();

                if ($existingPenggajian) {
                    continue; // Skip jika sudah ada
                }

                $penggajian = Penggajian::create([
                    'kontrak_id' => $kontrak->id,
                    'periode' => $periode,
                    'status' => 'belum_dibayar',
                    'total_gaji' => 0,
                ]);

                // Create detail penggajian untuk setiap karyawan
                $this->createPenggajianDetails($penggajian, $karyawans);
                
                // Calculate dan update total_gaji setelah semua details dibuat
                $totalGaji = $penggajian->penggajianDetails()->sum('total_gaji');
                $penggajian->update(['total_gaji' => $totalGaji]);

                $penggajianRecords[] = $penggajian;
            }

            return [
                'success' => true,
                'message' => 'Penggajian berhasil di-generate.',
                'created_count' => count($penggajianRecords),
                'periodes' => $periodes,
            ];
        });
    }

    /**
     * Validate kontrak sebelum generate penggajian
     * 
     * @param Kontrak $kontrak
     * @throws \Exception
     */
    private function validateKontrak(Kontrak $kontrak): void
    {
        if (!$kontrak->tanggal_gajian) {
            throw new \Exception('Kontrak belum menetapkan tanggal gajian.');
        }

        // Extract day from tanggal_gajian
        // Could be: "7", 7, "2026-01-07", etc.
        $tanggalGajianValue = $kontrak->tanggal_gajian;
        
        if (strlen($tanggalGajianValue) > 2) {
            // If it's a date string like "2026-01-07", extract the day
            $tanggalGajian = (int) date('d', strtotime($tanggalGajianValue));
        } else {
            // If it's already just a day number
            $tanggalGajian = (int) $tanggalGajianValue;
        }

        if ($tanggalGajian < 1 || $tanggalGajian > 31) {
            throw new \Exception('Tanggal gajian harus antara 1-31. Nilai yang diterima: ' . $tanggalGajian);
        }

        $tanggalMulai = Carbon::parse($kontrak->tanggal_mulai);
        $tanggalSelesai = Carbon::parse($kontrak->tanggal_selesai);

        if ($tanggalMulai >= $tanggalSelesai) {
            throw new \Exception('Tanggal mulai harus lebih awal dari tanggal selesai.');
        }
    }

    /**
     * Generate array periode gajian dalam rentang tanggal kontrak
     * 
     * @param Carbon $tanggalMulai
     * @param Carbon $tanggalSelesai
     * @param int $tanggalGajian
     * @return array
     */
    private function generatePeriodes(Carbon $tanggalMulai, Carbon $tanggalSelesai, int $tanggalGajian): array
    {
        $periodes = [];
        $current = $tanggalMulai->copy()->startOfMonth();

        while ($current <= $tanggalSelesai) {
            // Tentukan tanggal gajian di bulan ini
            $hariMaksimal = $current->copy()->endOfMonth()->day;
            $hari = min($tanggalGajian, $hariMaksimal);
            $tanggalPenggajian = $current->copy()->day($hari);

            // Cek apakah tanggal gajian berada dalam rentang kontrak
            if ($tanggalPenggajian >= $tanggalMulai && $tanggalPenggajian <= $tanggalSelesai) {
                $periodes[] = $tanggalPenggajian->format('Y-m-d');
            }

            $current->addMonth();
        }

        return $periodes;
    }

    /**
     * Create penggajian detail untuk setiap karyawan
     * 
     * @param Penggajian $penggajian
     * @param Collection $kontrakKaryawans
     */
    private function createPenggajianDetails(Penggajian $penggajian, Collection $kontrakKaryawans): void
    {
        foreach ($kontrakKaryawans as $kontrakKaryawan) {
            $karyawan = $kontrakKaryawan->karyawan;
            
            // Safely get gaji and bpjs from jabatan (field name is 'gaji' not 'gaji_pokok')
            $gajiPokok = (float) ($karyawan->jabatan?->gaji ?? 0);
            $bpjs = (float) ($karyawan->jabatan?->bpjs ?? 0);

            // Calculate total cashbon belum_dibayar
            $potonganCashbon = 0;
            if ($karyawan->relationLoaded('cashbons')) {
                $potonganCashbon = (float) $karyawan->cashbons
                    ->where('status', 'belum_dibayar')
                    ->sum('jumlah');
            } else {
                $potonganCashbon = (float) ($karyawan->cashbons()
                    ->where('status', 'belum_dibayar')
                    ->sum('jumlah') ?? 0);
            }

            $totalGaji = $gajiPokok - $bpjs - $potonganCashbon;

            $penggajian->penggajianDetails()->create([
                'karyawan_id' => $karyawan->id,
                'gaji_pokok' => $gajiPokok,
                'bpjs' => $bpjs,
                'potongan_cashbon' => $potonganCashbon,
                'total_gaji' => max(0, $totalGaji), // Ensure non-negative
            ]);
        }
    }
}
