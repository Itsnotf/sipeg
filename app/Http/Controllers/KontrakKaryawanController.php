<?php

namespace App\Http\Controllers;

use App\Http\Requests\KontrakKaryawan\StoreRequest;
use App\Models\Karyawan;
use App\Models\KontrakKaryawan;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class KontrakKaryawanController extends Controller implements HasMiddleware
{
      public static function middleware()
    {
        return [
            new Middleware('permission:kontraks karyawans index', only: ['index']),
            new Middleware('permission:kontraks karyawans create', only: ['create', 'store']),
            new Middleware('permission:kontraks karyawans delete', only: ['destroy']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index(string $kontrak_id, Request $request)
    {
        $karyawans = KontrakKaryawan::with('kontrak', 'karyawan')->when($request->search, function ($query, $search) {
            $query->where('nama_karyawan', 'like', "%{$search}%")
                ->orWhere('file', 'like', "%{$search}%");
        })
            ->where('kontrak_id', $kontrak_id)
            ->paginate(8)
            ->withQueryString();

        return inertia('kontraks/karyawans/index', [
            'karyawans' => $karyawans,
            'kontrak_id' => $kontrak_id,
            'filters' => $request->only('search'),
            'flash' => [
                'success' => session('success'),
            ],
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create(string $kontrak_id)
    {
        $karyawans = Karyawan::with('jabatan')->where('status', 'Non Aktif')->get();
        return Inertia::render('kontraks/karyawans/create', [
            'kontrak_id' => $kontrak_id,
            'karyawans' => $karyawans,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(string $kontrak_id, StoreRequest $request)
    {
        $validated = $request->validated();
        $karyawanIds = $validated['karyawan_id'];

        // Cek apakah karyawan sudah ada di kontrak yang sama
        $existingKaryawans = KontrakKaryawan::where('kontrak_id', $kontrak_id)
            ->whereIn('karyawan_id', $karyawanIds)
            ->pluck('karyawan_id')
            ->toArray();

        if (!empty($existingKaryawans)) {
            return redirect()->back()->with('error', 'Beberapa karyawan sudah terdaftar di kontrak ini');
        }

        try {
            // Insert multiple karyawan sekaligus
            $data = array_map(function ($karyawanId) use ($kontrak_id) {
                return [
                    'kontrak_id' => $kontrak_id,
                    'karyawan_id' => $karyawanId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $karyawanIds);

            KontrakKaryawan::insert($data);

            // Update status karyawan menjadi Aktif
            Karyawan::whereIn('id', $karyawanIds)->update(['status' => 'Aktif']);

            return redirect()->route('kontraks.karyawans.index', $kontrak_id)
                ->with('success', 'Karyawan berhasil ditambahkan ke kontrak dan status diperbarui menjadi Aktif');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(KontrakKaryawan $kontrakKaryawan)
    {
        //
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $kontrak_id, string $karyawan_id)
    {
        try {
            // Jika child_id adalah ID dari kontrak_karyawan (bukan karyawan_id)
            // Coba ambil dari ID kontrak_karyawan terlebih dahulu
            $kontrakKaryawan = KontrakKaryawan::where('kontrak_id', $kontrak_id)
                ->where('id', $karyawan_id)
                ->first();

            // Jika tidak ketemu, coba cari by karyawan_id
            if (!$kontrakKaryawan) {
                $kontrakKaryawan = KontrakKaryawan::where('kontrak_id', $kontrak_id)
                    ->where('karyawan_id', $karyawan_id)
                    ->first();
            }

            if (!$kontrakKaryawan) {
                return redirect()->route('kontraks.karyawans.index', $kontrak_id)
                    ->with('error', 'Karyawan tidak ditemukan di kontrak ini');
            }

            // Simpan karyawan_id sebelum delete
            $actualKaryawanId = $kontrakKaryawan->karyawan_id;

            // Delete record
            $kontrakKaryawan->delete();

            // Update status karyawan kembali ke Non Aktif
            Karyawan::where('id', $actualKaryawanId)->update(['status' => 'Non Aktif']);

            return redirect()->route('kontraks.karyawans.index', $kontrak_id)
                ->with('success', 'Karyawan berhasil dihapus dari kontrak dan status dikembalikan ke Non Aktif');
        } catch (\Exception $e) {
            return redirect()->route('kontraks.karyawans.index', $kontrak_id)
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}