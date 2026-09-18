<?php

namespace App\Http\Controllers;

use App\Http\Requests\Penggajian\UpdateStatusRequest;
use App\Models\Kontrak;
use App\Models\Penggajian;
use App\Services\PenggajianService;
use App\Support\JadwalGajian;
use App\Support\PeriodeGajian;
use App\Support\PesanKesalahan;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Throwable;

class PenggajianController extends Controller implements HasMiddleware
{
    public function __construct(
        private PenggajianService $penggajianService,
        private JadwalGajian $jadwal,
    ) {}

    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:penggajians index', only: ['index', 'indexGlobal']),
            new Middleware('permission:penggajians show', only: ['show']),
            new Middleware('permission:penggajians generate', only: ['generate']),
            new Middleware('permission:penggajians update', only: ['update']),
        ];
    }

    public function index($kontrak_id, Request $request)
    {
        $kontrak = Kontrak::with('client')->findOrFail($kontrak_id);

        $penggajians = Penggajian::where('kontrak_id', $kontrak_id)
            ->withCount('penggajianDetails')
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('periode', 'desc')
            ->paginate(10)
            ->withQueryString();

        return inertia('kontraks/penggajians/index', [
            'kontrak' => $kontrak,
            'kontrak_id' => $kontrak_id,
            'penggajians' => $penggajians,
            'jadwal' => $this->jadwalMendatang($kontrak),
            'filters' => $request->only('status'),
        ]);
    }

    public function indexGlobal(Request $request)
    {
        $penggajians = Penggajian::with(['kontrak.client'])
            ->withCount('penggajianDetails')
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('periode', 'desc')
            ->paginate(10)
            ->withQueryString();

        return inertia('penggajians/index', [
            'penggajians' => $penggajians,
            'filters' => $request->only('status'),
        ]);
    }

    public function show($kontrak_id, $penggajian_id)
    {
        $kontrak = Kontrak::with('client')->findOrFail($kontrak_id);

        $penggajian = Penggajian::where('kontrak_id', $kontrak_id)
            ->with(['penggajianDetails.karyawan.jabatan', 'penggajianDetails.potongans.cashbon'])
            ->findOrFail($penggajian_id);

        $details = $penggajian->penggajianDetails->map(fn ($detail): array => [
            'id' => $detail->id,
            'karyawan_id' => $detail->karyawan_id,
            'gaji_pokok_penuh' => (float) $detail->gaji_pokok_penuh,
            'gaji_pokok' => (float) $detail->gaji_pokok,
            'bpjs_persen' => (float) $detail->bpjs_persen,
            'bpjs' => (float) $detail->bpjs,
            'potongan_cashbon' => (float) $detail->potongan_cashbon,
            'total_gaji' => (float) $detail->total_gaji,
            'hari_aktif' => $detail->hari_aktif,
            'hari_periode' => $detail->hari_periode,
            'karyawan' => [
                'id' => $detail->karyawan->id,
                'nama' => $detail->karyawan->nama,
                'jabatan' => [
                    'nama_jabatan' => $detail->karyawan->jabatan?->nama_jabatan ?? '-',
                ],
            ],
            // Rincian asal setiap rupiah potongan, langsung dari buku besar
            'potongans' => $detail->potongans->map(fn ($potongan): array => [
                'id' => $potongan->id,
                'jumlah' => (float) $potongan->jumlah,
                'keterangan' => $potongan->cashbon?->keterangan ?? '-',
                'pinjaman' => (float) ($potongan->cashbon?->jumlah ?? 0),
                'sisa' => $potongan->cashbon?->sisaHutang() ?? 0,
            ])->all(),
        ])->all();

        return inertia('kontraks/penggajians/show', [
            'kontrak' => $kontrak,
            'kontrak_id' => $kontrak_id,
            'penggajian' => [
                'id' => $penggajian->id,
                'kontrak_id' => $penggajian->kontrak_id,
                'periode' => $penggajian->periode,
                'periode_mulai' => $penggajian->periode_mulai?->format('Y-m-d'),
                'periode_selesai' => $penggajian->periode_selesai?->format('Y-m-d'),
                'tanggal_bayar' => $penggajian->tanggal_bayar?->format('Y-m-d'),
                'final' => (bool) $penggajian->final,
                'status' => $penggajian->status,
                'created_at' => $penggajian->created_at,
                'updated_at' => $penggajian->updated_at,
                'penggajianDetails' => $details,
            ],
            'summary' => [
                'total_gaji' => (float) $penggajian->penggajianDetails->sum('total_gaji'),
                'total_bpjs' => (float) $penggajian->penggajianDetails->sum('bpjs'),
                'total_cashbon' => (float) $penggajian->penggajianDetails->sum('potongan_cashbon'),
                'karyawan_count' => $penggajian->penggajianDetails->count(),
            ],
        ]);
    }

    /**
     * Memproses seluruh periode yang tanggal bayarnya sudah tiba dan belum
     * pernah dibuat. Periode mendatang sengaja tidak dibuat di muka — record
     * masa depan harus terus disinkronkan setiap roster, gaji, atau cashbon
     * berubah, dan itulah sumber ketidakkonsistenan pada rancangan lama.
     */
    public function generate(Request $request, $kontrak_id)
    {
        try {
            $kontrak = Kontrak::with('client')->findOrFail($kontrak_id);
            $dibuat = $this->penggajianService->proses($kontrak);

            $pesan = $dibuat === []
                ? 'Tidak ada periode baru yang jatuh tempo. Semua periode yang sudah waktunya telah diproses.'
                : sprintf('%d periode penggajian berhasil diproses.', count($dibuat));

            return redirect()
                ->route('kontraks.penggajians.index', $kontrak_id)
                ->with($dibuat === [] ? 'info' : 'success', $pesan);
        } catch (Throwable $e) {
            return redirect()->back()->with('error', PesanKesalahan::untukPengguna($e, 'Penggajian gagal diproses.'));
        }
    }

    /**
     * Menandai penggajian sebagai sudah dibayar. Transisi ini searah — lihat
     * UpdateStatusRequest.
     */
    public function update(UpdateStatusRequest $request, $kontrak_id, $penggajian_id)
    {
        try {
            $penggajian = Penggajian::where('kontrak_id', $kontrak_id)->findOrFail($penggajian_id);

            $this->penggajianService->tandaiDibayar($penggajian);

            return redirect()
                ->route('kontraks.penggajians.show', [$kontrak_id, $penggajian_id])
                ->with('success', 'Penggajian ditandai sudah dibayar.');
        } catch (Throwable $e) {
            return redirect()->back()->with('error', PesanKesalahan::untukPengguna($e, 'Status penggajian gagal diperbarui.'));
        }
    }

    /**
     * Periode yang belum dibuat, ditampilkan sebagai jadwal read-only.
     *
     * @return array<int, array<string, mixed>>
     */
    private function jadwalMendatang(Kontrak $kontrak): array
    {
        try {
            $sudahAda = Penggajian::where('kontrak_id', $kontrak->id)->pluck('periode')->all();

            return collect($this->jadwal->untuk($kontrak))
                ->reject(fn (PeriodeGajian $periode): bool => in_array($periode->periode, $sudahAda, true))
                ->map(fn (PeriodeGajian $periode): array => [
                    'periode' => $periode->periode,
                    'label' => $periode->label(),
                    'mulai' => $periode->mulai->format('Y-m-d'),
                    'selesai' => $periode->selesai->format('Y-m-d'),
                    'tanggal_bayar' => $periode->tanggalBayar->format('Y-m-d'),
                    'jatuh_tempo' => $periode->jatuhTempo(),
                ])
                ->values()
                ->all();
        } catch (Throwable) {
            // Kontrak dengan tanggal tidak valid tidak boleh membuat halaman gagal
            return [];
        }
    }
}
