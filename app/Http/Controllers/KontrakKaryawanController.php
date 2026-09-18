<?php

namespace App\Http\Controllers;

use App\Enums\StatusKaryawan;
use App\Http\Requests\KontrakKaryawan\StoreRequest;
use App\Models\Karyawan;
use App\Models\Kontrak;
use App\Models\KontrakKaryawan;
use App\Services\PenempatanKontrak;
use App\Support\PesanKesalahan;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class KontrakKaryawanController extends Controller implements HasMiddleware
{
    public function __construct(private PenempatanKontrak $penempatan) {}

    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
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
        $karyawans = KontrakKaryawan::with('karyawan.jabatan')->when($request->search, function ($query, $search) {
            // Kolom pencarian sebelumnya menunjuk nama_karyawan dan file, yang
            // tidak ada pada tabel ini — pencarian selalu gagal.
            $query->whereHas('karyawan', function ($karyawanQuery) use ($search) {
                $karyawanQuery->where('nama', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%");
            });
        })
            ->where('kontrak_id', $kontrak_id)
            ->paginate(8)
            ->withQueryString()
            ->through(fn (KontrakKaryawan $penempatan): array => [
                'id' => $penempatan->id,
                'kontrak_id' => $penempatan->kontrak_id,
                'nama' => $penempatan->karyawan?->nama,
                'nik' => $penempatan->karyawan?->nik,
                'jabatan' => $penempatan->karyawan?->jabatan?->nama_jabatan,
                'tanggal_mulai' => $penempatan->tanggal_mulai?->format('Y-m-d'),
                'tanggal_selesai' => $penempatan->tanggal_selesai?->format('Y-m-d'),
            ]);

        $kontrak = Kontrak::findOrFail($kontrak_id);

        return inertia('kontraks/karyawans/index', [
            'karyawans' => $karyawans,
            'kontrak_id' => $kontrak_id,
            'kontrak' => ['id' => $kontrak->id, 'judul' => $kontrak->judul],
            'filters' => $request->only('search'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(string $kontrak_id)
    {
        $karyawans = Karyawan::with('jabatan')
            ->where('status', StatusKaryawan::NonAktif)
            ->orderBy('nama')
            ->get()
            ->map(fn (Karyawan $karyawan): array => [
                'id' => $karyawan->id,
                'nama' => $karyawan->nama,
                'nik' => $karyawan->nik,
                'jabatan' => $karyawan->jabatan?->nama_jabatan,
                'gaji' => (float) ($karyawan->jabatan?->gaji ?? 0),
            ])
            ->all();

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

        // Hanya penempatan yang MASIH berjalan yang dianggap duplikat. Tanpa
        // batasan itu, pekerja yang penempatannya sudah diakhiri tidak pernah
        // bisa ditempatkan kembali di kontrak yang sama.
        $existingKaryawans = KontrakKaryawan::aktif()
            ->where('kontrak_id', $kontrak_id)
            ->whereIn('karyawan_id', $karyawanIds)
            ->pluck('karyawan_id')
            ->toArray();

        if (! empty($existingKaryawans)) {
            return redirect()->back()->with('error', 'Beberapa karyawan sudah terdaftar di kontrak ini');
        }

        $kontrak = Kontrak::findOrFail($kontrak_id);

        // Satu pekerja hanya boleh memiliki satu penempatan aktif. Plafon
        // potongan dihitung per penggajian sementara hutang bersifat per
        // pekerja, sehingga penempatan ganda membuat plafon yang sama
        // diterapkan dua kali pada hutang yang sama.
        $penempatanLain = KontrakKaryawan::aktif()
            ->whereIn('karyawan_id', $karyawanIds)
            ->where('kontrak_id', '!=', $kontrak_id)
            ->exists();

        // Kontrak yang sudah berakhir tidak bisa menerima pekerja baru: ia akan
        // ditandai Aktif namun tidak pernah mendapat satu pun baris penggajian.
        if ($kontrak->tanggal_selesai !== null && now()->startOfDay()->gt($kontrak->tanggal_selesai)) {
            return redirect()->back()->with(
                'error',
                'Kontrak ini sudah berakhir, sehingga tidak dapat menerima penempatan baru.'
            );
        }

        if ($penempatanLain) {
            return redirect()->back()->with(
                'error',
                'Sebagian karyawan masih memiliki penempatan aktif di kontrak lain. Akhiri penempatan tersebut lebih dahulu.'
            );
        }

        try {
            DB::transaction(function () use ($karyawanIds, $kontrak): void {
                // Penempatan dimulai hari ini, atau saat kontrak mulai bila
                // kontraknya belum berjalan — pekerja tidak boleh dibayar untuk
                // hari sebelum ia benar-benar ditempatkan.
                $mulai = now()->startOfDay()->max($kontrak->tanggal_mulai);

                foreach ($karyawanIds as $karyawanId) {
                    KontrakKaryawan::create([
                        'kontrak_id' => $kontrak->id,
                        'karyawan_id' => $karyawanId,
                        'tanggal_mulai' => $mulai->format('Y-m-d'),
                    ]);
                }

                Karyawan::whereIn('id', $karyawanIds)->update(['status' => StatusKaryawan::Aktif]);

                $this->penempatan->susunUlangBelumDibayar($kontrak);
            });

            return redirect()->route('kontraks.karyawans.index', $kontrak_id)
                ->with('success', 'Karyawan berhasil ditambahkan ke kontrak.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', PesanKesalahan::untukPengguna($e, 'Penempatan karyawan gagal disimpan.'));
        }
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
            if (! $kontrakKaryawan) {
                $kontrakKaryawan = KontrakKaryawan::where('kontrak_id', $kontrak_id)
                    ->where('karyawan_id', $karyawan_id)
                    ->first();
            }

            if (! $kontrakKaryawan) {
                return redirect()->route('kontraks.karyawans.index', $kontrak_id)
                    ->with('error', 'Karyawan tidak ditemukan di kontrak ini');
            }

            // Penempatan diakhiri, bukan dihapus. Menghapus barisnya akan
            // membuat perhitungan ulang menghilangkan baris detail pekerja ini
            // — beserta hari yang sudah benar-benar dikerjakannya.
            $this->penempatan->akhiri($kontrakKaryawan);

            return redirect()->route('kontraks.karyawans.index', $kontrak_id)
                ->with('success', 'Penempatan karyawan berhasil diakhiri.');
        } catch (\Exception $e) {
            return redirect()->route('kontraks.karyawans.index', $kontrak_id)
                ->with('error', PesanKesalahan::untukPengguna($e, 'Penempatan karyawan gagal diakhiri.'));
        }
    }
}
