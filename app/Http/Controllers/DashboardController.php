<?php

namespace App\Http\Controllers;

use App\Models\Kontrak;
use App\Models\Karyawan;
use App\Models\Penggajian;
use App\Models\Cashbon;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Get key statistics
        $totalKontraks = Kontrak::count();
        $activeKontraks = Kontrak::where('status', 'aktif')->count();
        $totalKaryawans = Karyawan::count();
        $totalPenggajians = Penggajian::count();

        // Get financial data
        $totalBiayaKontraks = Kontrak::sum('total_biaya');
        $totalPenggajianDisburse = Penggajian::with('penggajianDetails')
            ->get()
            ->sum(function ($penggajian) {
                return $penggajian->penggajianDetails->sum('total_gaji');
            });

        // Get recent kontraks
        $recentKontraks = Kontrak::with('client')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($kontrak) {
                return [
                    'id' => $kontrak->id,
                    'judul' => $kontrak->judul,
                    'client_name' => $kontrak->client->nama_client,
                    'status' => $kontrak->status,
                    'total_biaya' => (string) $kontrak->total_biaya,
                    'tanggal_mulai' => $kontrak->tanggal_mulai,
                ];
            });

        // Get recent penggajians
        $recentPenggajians = Penggajian::with(['kontrak', 'penggajianDetails'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($penggajian) {
                $totalGaji = $penggajian->penggajianDetails->sum('total_gaji');
                return [
                    'id' => $penggajian->id,
                    'kontrak_id' => $penggajian->kontrak_id,
                    'kontrak_judul' => $penggajian->kontrak->judul,
                    'periode' => $penggajian->periode,
                    'status' => $penggajian->status,
                    'total_gaji' => (string) $totalGaji,
                ];
            });

        // Get pending penggajians (belum_dibayar)
        $pendingPenggajians = Penggajian::where('status', 'belum_dibayar')
            ->with(['kontrak', 'penggajianDetails'])
            ->count();

        $pendingTotalGaji = Penggajian::where('status', 'belum_dibayar')
            ->with('penggajianDetails')
            ->get()
            ->sum(function ($penggajian) {
                return $penggajian->penggajianDetails->sum('total_gaji');
            });

        // Get cashbon data
        $totalCashbons = Cashbon::sum('jumlah');
        $pendingCashbons = Cashbon::where('status', 'belum_dibayar')->count();

        return inertia('dashboard', [
            'statistics' => [
                'total_kontraks' => $totalKontraks,
                'active_kontraks' => $activeKontraks,
                'total_karyawans' => $totalKaryawans,
                'total_penggajians' => $totalPenggajians,
            ],
            'financial' => [
                'total_biaya_kontraks' => (float) $totalBiayaKontraks,
                'total_penggajian_dikeluarkan' => (float) $totalPenggajianDisburse,
                'keuntungan_bersih' => (float) $totalBiayaKontraks - $totalPenggajianDisburse,
            ],
            'penggajian_status' => [
                'pending_count' => $pendingPenggajians,
                'pending_total_gaji' => (float) $pendingTotalGaji,
            ],
            'cashbon_status' => [
                'total' => (float) $totalCashbons,
                'pending_count' => $pendingCashbons,
            ],
            'recent_kontraks' => $recentKontraks,
            'recent_penggajians' => $recentPenggajians,
        ]);
    }
}
