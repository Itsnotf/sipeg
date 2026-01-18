<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cashbon\StoreRequest;
use App\Http\Requests\Cashbon\UpdateRequest;
use App\Models\Cashbon;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class CashbonController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('permission:cashbons index', only: ['index']),
            new Middleware('permission:cashbons create', only: ['create', 'store']),
            new Middleware('permission:cashbons edit', only: ['edit', 'update']),
            new Middleware('permission:cashbons delete', only: ['destroy']),
        ];
    }

    private function normalizeAmount(mixed $value): int
    {
        if ($value === null) {
            return 0;
        }

        $digits = preg_replace('/[^0-9]/', '', (string) $value);

        return $digits === '' ? 0 : (int) $digits;
    }

    private function sumUnpaidCashbonJumlahForKaryawan(int $karyawanId): int
    {
        $jumlahValues = Cashbon::query()
            ->where('karyawan_id', $karyawanId)
            ->where('status', 'belum dibayar')
            ->pluck('jumlah');

        $total = 0;
        foreach ($jumlahValues as $jumlah) {
            $total += $this->normalizeAmount($jumlah);
        }

        return $total;
    }

    private function sumUnpaidCashbonJumlahForKaryawanExcluding(int $karyawanId, int $excludeCashbonId): int
    {
        $jumlahValues = Cashbon::query()
            ->where('karyawan_id', $karyawanId)
            ->where('status', 'belum dibayar')
            ->where('id', '!=', $excludeCashbonId)
            ->pluck('jumlah');

        $total = 0;
        foreach ($jumlahValues as $jumlah) {
            $total += $this->normalizeAmount($jumlah);
        }

        return $total;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $cashbons = Cashbon::with('karyawan.jabatan')
            ->when($request->search, function ($query, $search) {
                $query->where('keterangan', 'like', "%{$search}%")
                    ->orWhereHas('karyawan', function ($karyawanQuery) use ($search) {
                        $karyawanQuery->where('nama', 'like', "%{$search}%")
                            ->orWhere('nik', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->paginate(8)
            ->withQueryString();

        return inertia('cashbons/index', [
            'cashbons' => $cashbons,
            'filters' => $request->only('search'),
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $karyawans = Karyawan::with('jabatan')->get();

        return Inertia::render('cashbons/create', [
            'karyawans' => $karyawans,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        $validated = $request->validated();

        $validated['status'] = $validated['status'] ?? 'belum dibayar';

        $karyawan = Karyawan::with('jabatan')->findOrFail($validated['karyawan_id']);
        $gaji = $this->normalizeAmount($karyawan->jabatan?->gaji);
        $limit = (int) floor($gaji * 0.5);

        $jumlahBaru = $this->normalizeAmount($validated['jumlah']);
        $totalSebelumnya = $this->sumUnpaidCashbonJumlahForKaryawan((int) $karyawan->id);
        $jumlahBaruTerhitung = $validated['status'] === 'belum dibayar' ? $jumlahBaru : 0;
        $totalSesudahnya = $totalSebelumnya + $jumlahBaruTerhitung;

        if ($limit <= 0) {
            return redirect()->back()->with('error', 'Cashbon tidak dapat dibuat karena gaji karyawan belum tersedia.');
        }

        if ($totalSesudahnya > $limit) {
            return redirect()->back()->with('error', 'Cashbon tidak dapat dibuat karena sudah lebih dari limit 50% gaji.');
        }

        $validated['jumlah'] = (string) $jumlahBaru;

        Cashbon::create($validated);

        return redirect()->route('cashbons.index')->with('success', 'Cashbon created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Cashbon $cashbon)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $cashbon = Cashbon::with('karyawan.jabatan')->findOrFail($id);
        $karyawans = Karyawan::with('jabatan')->get();

        return Inertia::render('cashbons/edit', [
            'cashbon' => $cashbon,
            'karyawans' => $karyawans,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, string $id)
    {
        $cashbon = Cashbon::findOrFail($id);

        if ($cashbon->created_at && $cashbon->created_at->lt(now()->subDay())) {
            return redirect()->back()->with('error', 'Cashbon tidak dapat diupdate karena sudah lebih dari 1 hari.');
        }

        $validated = $request->validated();

        $validated['status'] = $validated['status'] ?? $cashbon->status ?? 'belum dibayar';

        $jumlahBaru = $this->normalizeAmount($validated['jumlah']);
        $validated['jumlah'] = (string) $jumlahBaru;

        $karyawanIdBaru = (int) $validated['karyawan_id'];
        $karyawan = Karyawan::with('jabatan')->findOrFail($karyawanIdBaru);
        $gaji = $this->normalizeAmount($karyawan->jabatan?->gaji);
        $limit = (int) floor($gaji * 0.5);

        $totalSebelumnya = $this->sumUnpaidCashbonJumlahForKaryawanExcluding($karyawanIdBaru, (int) $cashbon->id);
        $jumlahBaruTerhitung = $validated['status'] === 'belum dibayar' ? $jumlahBaru : 0;
        $totalSesudahnya = $totalSebelumnya + $jumlahBaruTerhitung;

        if ($limit <= 0) {
            return redirect()->back()->with('error', 'Cashbon tidak dapat diupdate karena gaji karyawan belum tersedia.');
        }

        if ($totalSesudahnya > $limit) {
            return redirect()->back()->with('error', 'Cashbon tidak dapat diupdate karena sudah lebih dari limit 50% gaji.');
        }

        $cashbon->update($validated);

        return redirect()->route('cashbons.index')->with('success', 'Cashbon updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $cashbon = Cashbon::findOrFail($id);

        if ($cashbon->created_at && $cashbon->created_at->lt(now()->subDay())) {
            return redirect()->back()->with('error', 'Cashbon tidak dapat dihapus karena sudah lebih dari 1 hari.');
        }

        $cashbon->delete();

        return redirect()->route('cashbons.index')->with('success', 'Cashbon deleted successfully.');
    }
}
