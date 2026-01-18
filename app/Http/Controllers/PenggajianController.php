<?php

namespace App\Http\Controllers;

use App\Http\Requests\Penggajian\UpdateStatusRequest;
use App\Models\Kontrak;
use App\Models\Penggajian;
use App\Services\PenggajianService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PenggajianController extends Controller implements HasMiddleware
{
    protected $penggajianService;

    public function __construct(PenggajianService $penggajianService)
    {
        $this->penggajianService = $penggajianService;
    }

    public static function middleware()
    {
        return [
            new Middleware('permission:penggajians index', only: ['index', 'indexGlobal']),
            new Middleware('permission:penggajians show', only: ['show']),
            new Middleware('permission:penggajians generate', only: ['generate']),
            new Middleware('permission:penggajians update', only: ['update']),
        ];
    }

    /**
     * Display a listing of penggajian for a contract
     */
    public function index($kontrak_id, Request $request)
    {
        $kontrak = Kontrak::findOrFail($kontrak_id);

        $penggajians = Penggajian::where('kontrak_id', $kontrak_id)
            ->with(['penggajianDetails'])
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('periode', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Transform to add penggajian_details_count
        $penggajians->getCollection()->transform(function ($penggajian) {
            return array_merge($penggajian->toArray(), [
                'penggajian_details_count' => $penggajian->penggajianDetails->count(),
            ]);
        });

        return inertia('kontraks/penggajians/index', [
            'kontrak' => $kontrak,
            'kontrak_id' => $kontrak_id,
            'penggajians' => $penggajians,
            'filters' => $request->only('status'),
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ],
        ]);
    }

    /**
     * Display a listing of all penggajian globally
     */
    public function indexGlobal(Request $request)
    {
        $penggajians = Penggajian::with(['kontrak.client', 'penggajianDetails.karyawan.jabatan'])
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('periode', 'desc')
            ->paginate(10)
            ->withQueryString();

        return inertia('penggajians/index', [
            'penggajians' => $penggajians,
            'filters' => $request->only('status'),
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ],
        ]);
    }

    /**
     * Display the specified penggajian
     */
    public function show($kontrak_id, $penggajian_id)
    {
        $kontrak = Kontrak::findOrFail($kontrak_id);
        $penggajian = Penggajian::where('kontrak_id', $kontrak_id)
            ->with(['penggajianDetails.karyawan.jabatan'])
            ->findOrFail($penggajian_id);

        // Ensure penggajianDetails is loaded
        if (!$penggajian->relationLoaded('penggajianDetails')) {
            $penggajian->load('penggajianDetails.karyawan.jabatan');
        }

        // Calculate summary using collection (not query builder)
        $totalGaji = $penggajian->penggajianDetails->sum('total_gaji');
        $totalBpjs = $penggajian->penggajianDetails->sum('bpjs');
        $totalCashbon = $penggajian->penggajianDetails->sum('potongan_cashbon');
        $karyawanCount = $penggajian->penggajianDetails->count();

        // Convert penggajianDetails to array with only needed fields
        $penggajianDetailsArray = $penggajian->penggajianDetails->map(function ($detail) {
            return [
                'id' => $detail->id,
                'karyawan_id' => $detail->karyawan_id,
                'gaji_pokok' => (string) $detail->gaji_pokok,
                'bpjs' => (string) $detail->bpjs,
                'potongan_cashbon' => (string) $detail->potongan_cashbon,
                'total_gaji' => (string) $detail->total_gaji,
                'karyawan' => [
                    'id' => $detail->karyawan->id,
                    'nama' => $detail->karyawan->nama,
                    'jabatan' => [
                        'nama_jabatan' => $detail->karyawan->jabatan?->nama_jabatan ?? '-',
                    ],
                ],
            ];
        })->all();

        return inertia('kontraks/penggajians/show', [
            'kontrak' => $kontrak,
            'kontrak_id' => $kontrak_id,
            'penggajian' => [
                'id' => $penggajian->id,
                'kontrak_id' => $penggajian->kontrak_id,
                'periode' => $penggajian->periode,
                'status' => $penggajian->status,
                'created_at' => $penggajian->created_at,
                'updated_at' => $penggajian->updated_at,
                'penggajianDetails' => $penggajianDetailsArray,
            ],
            'summary' => [
                'total_gaji' => $totalGaji,
                'total_bpjs' => $totalBpjs,
                'total_cashbon' => $totalCashbon,
                'karyawan_count' => $karyawanCount,
            ],
        ]);
    }

    /**
     * Generate penggajian untuk kontrak
     */
    public function generate(Request $request, $kontrak_id)
    {
        // dd($kontrak_id);
        try {
            Log::info('Generate penggajian request received', [
                'kontrak_id' => $kontrak_id,
                'user' => Auth::id(),
            ]);
            
            $kontrak = Kontrak::with('kontrakKaryawans.karyawan.jabatan', 'kontrakKaryawans.karyawan.cashbons')->findOrFail($kontrak_id);
            
            $result = $this->penggajianService->generate($kontrak);

            return redirect()
                ->route('kontraks.penggajians.index', $kontrak_id)
                ->with('success', $result['message'] . ' (' . $result['created_count'] . ' periode)');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()
                ->back()
                ->with('error', 'Kontrak tidak ditemukan.');
        } catch (\Exception $e) {
            Log::error('Generate penggajian error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()
                ->back()
                ->with('error', 'Gagal generate penggajian: ' . $e->getMessage());
        }
    }

    /**
     * Update status penggajian
     */
    public function update(UpdateStatusRequest $request, $kontrak_id, $penggajian_id)
    {
        try {
            $kontrak = Kontrak::findOrFail($kontrak_id);
            $penggajian = Penggajian::where('kontrak_id', $kontrak_id)
                ->findOrFail($penggajian_id);

            $penggajian->update($request->validated());

            return redirect()
                ->route('kontraks.penggajians.show', [$kontrak_id, $penggajian_id])
                ->with('success', 'Status penggajian berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Gagal memperbarui status penggajian: ' . $e->getMessage());
        }
    }
}
