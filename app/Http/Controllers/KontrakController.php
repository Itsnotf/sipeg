<?php

namespace App\Http\Controllers;

use App\Http\Requests\Kontrak\StoreRequest;
use App\Http\Requests\Kontrak\UpdateRequest;
use App\Models\Client;
use App\Models\Kontrak;
use App\Models\Karyawan;
use App\Models\KontrakKaryawan;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class KontrakController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('permission:kontraks index', only: ['index']),
            new Middleware('permission:kontraks create', only: ['create', 'store']),
            new Middleware('permission:kontraks edit', only: ['edit', 'update   ']),
            new Middleware('permission:kontraks delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
     public function index(Request $request)
    {
        $Kontraks = Kontrak::with('client')->when($request->search, function ($query, $search) {
            $query->where('judul', 'like', "%{$search}%")
                ->orWhere('deskripsi', 'like', "%{$search}%");
        })
            ->paginate(8)
            ->withQueryString();

        return inertia('kontraks/index', [
            'kontraks' => $Kontraks,
            'filters' => $request->only('search'),
            'flash' => [
                'success' => session('success'),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $clients = Client::get();

        return inertia('kontraks/create', [
            'clients' => $clients,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        $validated = $request->validated();

        Kontrak::create($validated);

        return redirect()->route('kontraks.index')->with('success', 'Kontrak created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $kontrak = Kontrak::with(['client', 'kontrakDokumens', 'kontrakKaryawans.karyawan.jabatan', 'penggajians.penggajianDetails'])
            ->findOrFail($id);

        // Calculate total penggajian (sum of all penggajian total_gaji)
        $totalPenggajian = $kontrak->penggajians->sum(function ($penggajian) {
            return $penggajian->penggajianDetails->sum('total_gaji');
        });

        // Calculate keuntungan (total_biaya - totalPenggajian)
        $keuntungan = (float) $kontrak->total_biaya - $totalPenggajian;

        // Transform penggajians with penggajianDetails for proper JSON serialization
        $penggajians = $kontrak->penggajians->map(function ($penggajian) {
            return [
                'id' => $penggajian->id,
                'kontrak_id' => $penggajian->kontrak_id,
                'periode' => $penggajian->periode,
                'status' => $penggajian->status,
                'created_at' => $penggajian->created_at,
                'updated_at' => $penggajian->updated_at,
                'penggajianDetails' => $penggajian->penggajianDetails->map(function ($detail) {
                    return [
                        'id' => $detail->id,
                        'karyawan_id' => $detail->karyawan_id,
                        'gaji_pokok' => (string) $detail->gaji_pokok,
                        'bpjs' => (string) $detail->bpjs,
                        'potongan_cashbon' => (string) $detail->potongan_cashbon,
                        'total_gaji' => (string) $detail->total_gaji,
                    ];
                })->all(),
            ];
        })->all();

        return inertia('kontraks/show', [
            'kontrak' => [
                'id' => $kontrak->id,
                'client_id' => $kontrak->client_id,
                'judul' => $kontrak->judul,
                'deskripsi' => $kontrak->deskripsi,
                'tanggal_mulai' => $kontrak->tanggal_mulai,
                'tanggal_selesai' => $kontrak->tanggal_selesai,
                'total_biaya' => (string) $kontrak->total_biaya,
                'tanggal_gajian' => $kontrak->tanggal_gajian,
                'status' => $kontrak->status,
                'created_at' => $kontrak->created_at,
                'updated_at' => $kontrak->updated_at,
                'client' => $kontrak->client,
                'kontrak_dokumens' => $kontrak->kontrakDokumens,
                'kontrak_karyawans' => $kontrak->kontrakKaryawans,
                'penggajians' => $penggajians,
            ],
            'penggajian_summary' => [
                'total_penggajian' => $totalPenggajian,
                'total_biaya' => (float) $kontrak->total_biaya,
                'keuntungan' => $keuntungan,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $clients = Client::get();
        $kontrak = Kontrak::findOrFail($id);

        return inertia('kontraks/edit', [
            'clients' => $clients,
            'kontrak' => $kontrak,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, string $id)
    {
        $validated = $request->validated();
        $kontrak = Kontrak::findOrFail($id);
        
        $oldStatus = $kontrak->status;
        $newStatus = $validated['status'];

        try {
            $kontrak->update($validated);

            // Jika status berubah menjadi "Selesai", ubah semua pegawai menjadi Non Aktif
            if ($newStatus === 'Selesai' && $oldStatus !== 'Selesai') {
                $karyawanIds = KontrakKaryawan::where('kontrak_id', $id)
                    ->pluck('karyawan_id')
                    ->toArray();

                if (!empty($karyawanIds)) {
                    Karyawan::whereIn('id', $karyawanIds)->update(['status' => 'Non Aktif']);
                }
            }

            return redirect()->route('kontraks.index')->with('success', 'Kontrak updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $kontrak = Kontrak::findOrFail($id);

            // Ambil semua karyawan yang ada di kontrak ini
            $karyawanIds = KontrakKaryawan::where('kontrak_id', $id)
                ->pluck('karyawan_id')
                ->toArray();

            // Delete kontrak (cascade akan delete kontrak_karyawans)
            $kontrak->delete();

            // Set semua karyawan yang ada di kontrak menjadi Non Aktif
            if (!empty($karyawanIds)) {
                Karyawan::whereIn('id', $karyawanIds)->update(['status' => 'Non Aktif']);
            }

            return redirect()->route('kontraks.index')->with('success', 'Kontrak deleted successfully dan semua karyawan dikembalikan ke status Non Aktif.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
