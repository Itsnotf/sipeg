<?php

namespace App\Http\Controllers;

use App\Enums\StatusKaryawan;
use App\Enums\StatusKontrak;
use App\Enums\StatusPenggajian;
use App\Http\Requests\Kontrak\StoreRequest;
use App\Http\Requests\Kontrak\UpdateRequest;
use App\Models\Client;
use App\Models\Karyawan;
use App\Models\Kontrak;
use App\Models\KontrakKaryawan;
use App\Models\Penggajian;
use App\Services\PenempatanKontrak;
use App\Support\PesanKesalahan;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class KontrakController extends Controller implements HasMiddleware
{
    public function __construct(private PenempatanKontrak $penempatan) {}

    public static function middleware()
    {
        return [
            new Middleware('permission:kontraks index', only: ['index', 'show']),
            new Middleware('permission:kontraks create', only: ['create', 'store']),
            new Middleware('permission:kontraks edit', only: ['edit', 'update']),
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
            'opsi' => ['status' => StatusKontrak::options()],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        $validated = $request->validated();

        Kontrak::create($validated);

        return redirect()->route('kontraks.index')->with('success', 'Kontrak berhasil ditambahkan.');
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

        /*
        | Setiap tanggal diformat eksplisit di sini.
        |
        | Cast 'date:Y-m-d' hanya berlaku ketika model diserialisasi UTUH lewat
        | toArray(). Begitu atributnya diambil satu per satu ke dalam array
        | rakitan tangan seperti di bawah, cast itu terlewat dan Carbon memakai
        | toISOString() bawaannya — menghasilkan "2026-03-31T17:00:00.000000Z"
        | untuk tengah malam 1 April WIB.
        */
        $penggajians = $kontrak->penggajians->map(fn ($penggajian): array => [
            'id' => $penggajian->id,
            'kontrak_id' => $penggajian->kontrak_id,
            'periode' => $penggajian->periode,
            'periode_mulai' => $penggajian->periode_mulai?->format('Y-m-d'),
            'periode_selesai' => $penggajian->periode_selesai?->format('Y-m-d'),
            'tanggal_bayar' => $penggajian->tanggal_bayar?->format('Y-m-d'),
            'status' => $penggajian->status,
            'total_gaji' => (float) $penggajian->total_gaji,
            'karyawan_count' => $penggajian->penggajianDetails->count(),
        ])->all();

        $dokumens = $kontrak->kontrakDokumens->map(fn ($dokumen): array => [
            'id' => $dokumen->id,
            'nama_dokumen' => $dokumen->nama_dokumen,
            'file' => $dokumen->file,
            'created_at' => $dokumen->created_at?->format('Y-m-d'),
        ])->all();

        $penempatans = $kontrak->kontrakKaryawans->map(fn ($penempatan): array => [
            'id' => $penempatan->id,
            'karyawan_id' => $penempatan->karyawan_id,
            'tanggal_mulai' => $penempatan->tanggal_mulai?->format('Y-m-d'),
            'tanggal_selesai' => $penempatan->tanggal_selesai?->format('Y-m-d'),
            'karyawan' => [
                'id' => $penempatan->karyawan?->id,
                'nama' => $penempatan->karyawan?->nama ?? '—',
                'nik' => $penempatan->karyawan?->nik ?? '—',
                'status' => $penempatan->karyawan?->status,
                'jabatan' => $penempatan->karyawan?->jabatan?->nama_jabatan ?? '—',
            ],
        ])->all();

        return inertia('kontraks/show', [
            'kontrak' => [
                'id' => $kontrak->id,
                'client_id' => $kontrak->client_id,
                'judul' => $kontrak->judul,
                'deskripsi' => $kontrak->deskripsi,
                'tanggal_mulai' => $kontrak->tanggal_mulai?->format('Y-m-d'),
                'tanggal_selesai' => $kontrak->tanggal_selesai?->format('Y-m-d'),
                'total_biaya' => (float) $kontrak->total_biaya,
                'tanggal_gajian' => $kontrak->tanggal_gajian,
                'status' => $kontrak->status,
                'client' => [
                    'id' => $kontrak->client?->id,
                    'nama_client' => $kontrak->client?->nama_client ?? '—',
                    'email' => $kontrak->client?->email,
                    'no_hp' => $kontrak->client?->no_hp,
                ],
                'kontrak_dokumens' => $dokumens,
                'kontrak_karyawans' => $penempatans,
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
            'opsi' => ['status' => StatusKontrak::options()],
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

        // Nilai tervalidasi masih berupa string, sedangkan status pada model
        // sudah berupa enum — dibandingkan langsung, keduanya tidak pernah sama.
        $newStatus = StatusKontrak::from($validated['status']);

        // Tanggal kontrak menentukan jadwal periode. Mengubahnya setelah ada
        // penggajian akan menghasilkan periode kedua yang tumpang tindih —
        // batasan unik (kontrak_id, periode) tidak mencegahnya karena kuncinya
        // memang berbeda — sehingga bulan yang sama terbayar dua kali.
        if (Penggajian::where('kontrak_id', $kontrak->id)->exists()) {
            $terkunci = ['tanggal_mulai', 'tanggal_selesai', 'tanggal_gajian'];

            foreach ($terkunci as $kolom) {
                $lama = $kolom === 'tanggal_gajian'
                    ? (int) $kontrak->{$kolom}
                    : $kontrak->{$kolom}?->format('Y-m-d');

                $baru = $kolom === 'tanggal_gajian'
                    ? (int) $validated[$kolom]
                    : $validated[$kolom];

                if ($lama !== $baru) {
                    return redirect()->back()->withInput()->with(
                        'error',
                        'Tanggal kontrak tidak dapat diubah karena penggajian sudah pernah diproses. Buat kontrak baru untuk periode yang berbeda bila jadwalnya memang harus berubah.'
                    );
                }
            }
        }

        try {
            $kontrak->update($validated);

            /*
            | Kontrak selesai berarti penempatannya juga berakhir.
            |
            | Sebelumnya di sini hanya status karyawannya yang diubah menjadi
            | Non Aktif, sementara tanggal_selesai penempatannya dibiarkan
            | kosong. Pekerja itu lalu muncul di daftar "tersedia" — daftar itu
            | menyaring status Non Aktif — dan ditolak saat disimpan karena
            | penempatannya masih terhitung aktif. Buntu, tanpa jalan keluar
            | dari layar kontrak.
            */
            if ($newStatus === StatusKontrak::Selesai && $oldStatus !== StatusKontrak::Selesai) {
                $this->penempatan->akhiriSeluruhnya($kontrak->refresh());
            }

            return redirect()->route('kontraks.index')->with('success', 'Kontrak berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', PesanKesalahan::untukPengguna($e, 'Kontrak gagal disimpan.'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $kontrak = Kontrak::findOrFail($id);

            // Menghapus kontrak akan menghapus berantai seluruh penggajiannya
            // beserta buku besar potongan cashbon di dalamnya — membuat hutang
            // yang sudah lunas hidup kembali sebagai hutang berjalan.
            $adaPenggajianTerbayar = Penggajian::where('kontrak_id', $kontrak->id)
                ->where('status', StatusPenggajian::Dibayar)
                ->exists();

            if ($adaPenggajianTerbayar) {
                return redirect()->route('kontraks.index')->with(
                    'error',
                    'Kontrak ini memiliki penggajian yang sudah dibayar dan tidak dapat dihapus.'
                );
            }

            // Ambil semua karyawan yang ada di kontrak ini
            $karyawanIds = KontrakKaryawan::where('kontrak_id', $id)
                ->pluck('karyawan_id')
                ->toArray();

            // Delete kontrak (cascade akan delete kontrak_karyawans)
            $kontrak->delete();

            // Set semua karyawan yang ada di kontrak menjadi Non Aktif
            if (! empty($karyawanIds)) {
                Karyawan::whereIn('id', $karyawanIds)->update(['status' => StatusKaryawan::NonAktif]);
            }

            return redirect()->route('kontraks.index')->with('success', 'Kontrak berhasil dihapus; seluruh pekerjanya dikembalikan ke status Non Aktif.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', PesanKesalahan::untukPengguna($e, 'Kontrak gagal dihapus.'));
        }
    }
}
