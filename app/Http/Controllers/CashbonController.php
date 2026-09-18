<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cashbon\StoreRequest;
use App\Http\Requests\Cashbon\UpdateRequest;
use App\Models\Cashbon;
use App\Models\Karyawan;
use App\Services\CashbonService;
use App\Support\PesanKesalahan;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Throwable;

/**
 * Seluruh perubahan cashbon dialirkan lewat CashbonService, yang menyusun ulang
 * penggajian terdampak dalam transaksi yang sama. Controller hanya menerjemahkan
 * kegagalan service menjadi pesan bagi pengguna.
 */
class CashbonController extends Controller implements HasMiddleware
{
    public function __construct(private CashbonService $cashbonService) {}

    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:cashbons index', only: ['index']),
            new Middleware('permission:cashbons create', only: ['create', 'store']),
            new Middleware('permission:cashbons edit', only: ['edit', 'update']),
            new Middleware('permission:cashbons delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $cashbons = Cashbon::with('karyawan.jabatan')
            ->withSum('potongans', 'jumlah')
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

        // Tiga angka yang berbeda maknanya, semuanya diturunkan dari buku besar:
        //   terpotong — sudah dialokasikan ke suatu slip (termasuk yang belum dibayar)
        //   terbayar  — benar-benar dipotong dari gaji yang sudah dibayarkan
        //   sisa      — hutang yang masih ditanggung pekerja
        $cashbons->getCollection()->transform(fn (Cashbon $cashbon): array => array_merge(
            $cashbon->toArray(),
            [
                'terpotong' => $cashbon->terpotong(),
                'terbayar' => $cashbon->terbayar(),
                'sisa' => $cashbon->sisaHutang(),
                // Tanggal pinjaman diformat di sini; created_at mentahnya
                // sengaja tidak ikut terserialisasi.
                'tanggal' => $cashbon->created_at?->format('Y-m-d'),
            ]
        ));

        $semua = Cashbon::all();

        return inertia('cashbons/index', [
            'cashbons' => $cashbons,
            'ringkasan' => [
                'total' => (float) $semua->sum(fn (Cashbon $cashbon): float => (float) $cashbon->jumlah),
                'terbayar' => $semua->sum(fn (Cashbon $cashbon): float => $cashbon->terbayar()),
                'sisa' => $semua->sum(fn (Cashbon $cashbon): float => $cashbon->sisaHutang()),
                'jumlah_pinjaman' => $semua->count(),
                'berjalan' => $semua->filter(fn (Cashbon $cashbon): bool => $cashbon->sisaHutang() >= 1)->count(),
            ],
            'filters' => $request->only('search'),
        ]);
    }

    public function create()
    {
        return Inertia::render('cashbons/create', [
            'karyawans' => $this->karyawanDenganPlafon(),
            'kebijakan' => $this->kebijakan(),
        ]);
    }

    public function store(StoreRequest $request)
    {
        try {
            $this->cashbonService->buat($request->validated());

            return redirect()->route('cashbons.index')->with('success', 'Cashbon berhasil dibuat.');
        } catch (Throwable $e) {
            return redirect()->back()->withInput()->with('error', PesanKesalahan::untukPengguna($e, 'Cashbon gagal disimpan.'));
        }
    }

    public function edit(string $id)
    {
        return Inertia::render('cashbons/edit', [
            'cashbon' => Cashbon::with('karyawan.jabatan')->findOrFail($id),
            'karyawans' => $this->karyawanDenganPlafon(),
            'kebijakan' => $this->kebijakan(),
        ]);
    }

    public function update(UpdateRequest $request, string $id)
    {
        try {
            $this->cashbonService->ubah(Cashbon::findOrFail($id), $request->validated());

            return redirect()->route('cashbons.index')->with('success', 'Cashbon berhasil diperbarui.');
        } catch (Throwable $e) {
            return redirect()->back()->withInput()->with('error', PesanKesalahan::untukPengguna($e, 'Cashbon gagal disimpan.'));
        }
    }

    public function destroy(string $id)
    {
        try {
            $this->cashbonService->hapus(Cashbon::findOrFail($id));

            return redirect()->route('cashbons.index')->with('success', 'Cashbon berhasil dihapus.');
        } catch (Throwable $e) {
            return redirect()->back()->with('error', PesanKesalahan::untukPengguna($e, 'Cashbon gagal disimpan.'));
        }
    }

    /**
     * Daftar pekerja lengkap dengan plafon dan sisa ruang pinjamannya, agar
     * formulir dapat memberi tahu batasnya sebelum pengguna menekan simpan.
     *
     * @return array<int, array<string, mixed>>
     */
    private function karyawanDenganPlafon(): array
    {
        return Karyawan::with('jabatan')->get()->map(function (Karyawan $karyawan): array {
            $plafon = $this->cashbonService->plafonPinjaman($karyawan);
            $berjalan = $this->cashbonService->hutangBerjalan($karyawan);
            $bersih = $this->cashbonService->gajiBersihBulanan($karyawan);

            return [
                'id' => $karyawan->id,
                'nama' => $karyawan->nama,
                'nik' => $karyawan->nik,
                'jabatan' => [
                    'nama_jabatan' => $karyawan->jabatan?->nama_jabatan ?? '-',
                    'gaji' => (float) ($karyawan->jabatan?->gaji ?? 0),
                ],
                'gaji_bersih' => $bersih,
                'potongan_maksimal' => round($bersih * (float) config('payroll.batas_potongan', 0.5)),
                'plafon' => $plafon,
                'hutang_berjalan' => $berjalan,
                'sisa_plafon' => max(0, $plafon - $berjalan),
            ];
        })->all();
    }

    /**
     * Angka kebijakan yang dipakai formulir untuk menjelaskan batasnya kepada
     * pengguna sebelum mereka mengetik, bukan menolaknya setelah menekan simpan.
     *
     * @return array<string, float|int>
     */
    private function kebijakan(): array
    {
        return [
            'maks_hutang_bulan' => (float) config('payroll.maks_hutang_bulan', 3),
            'batas_potongan' => (float) config('payroll.batas_potongan', 0.5),
        ];
    }
}
