<?php

namespace App\Http\Controllers;

use App\Enums\JenisKelamin;
use App\Enums\StatusKaryawan;
use App\Enums\StatusPenggajian;
use App\Http\Requests\Karyawan\StoreRequest;
use App\Http\Requests\Karyawan\UpdateRequest;
use App\Models\Cashbon;
use App\Models\CashbonPotongan;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\PenggajianDetail;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class KaryawanController extends Controller implements HasMiddleware
{
    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:karyawans index', only: ['index']),
            new Middleware('permission:karyawans create', only: ['create', 'store']),
            new Middleware('permission:karyawans edit', only: ['edit', 'update']),
            new Middleware('permission:karyawans delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $karyawans = Karyawan::with('jabatan')->when($request->search, function ($query, $search) {
            $query->where('nama', 'like', "%{$search}%")
                ->orWhere('nik', 'like', "%{$search}%");
        })
            ->paginate(8)
            ->withQueryString();

        return inertia('karyawans/index', [
            'karyawans' => $karyawans,
            'filters' => $request->only('search'),
        ]);
    }

    public function create()
    {
        return Inertia::render('karyawans/create', [
            'jabatans' => Jabatan::get(),
            'opsi' => $this->opsi(),
        ]);
    }

    /**
     * Opsi select bersumber dari enum, bukan diketik ulang di frontend —
     * sebelumnya nilainya dihardcode terpisah di beberapa berkas tanpa
     * pengait apa pun ke definisi di sisi server.
     *
     * @return array<string, array<int, array{value: string, label: string}>>
     */
    private function opsi(): array
    {
        return [
            'jenis_kelamin' => JenisKelamin::options(),
        ];
    }

    public function store(StoreRequest $request)
    {
        // Pekerja baru selalu Non Aktif; ia menjadi Aktif hanya lewat penempatan.
        Karyawan::create([...$request->validated(), 'status' => StatusKaryawan::NonAktif]);

        return redirect()->route('karyawans.index')->with('success', 'Karyawan berhasil ditambahkan.');
    }

    public function edit(string $id)
    {
        return Inertia::render('karyawans/edit', [
            'karyawan' => Karyawan::findOrFail($id),
            'jabatans' => Jabatan::get(),
            'opsi' => $this->opsi(),
        ]);
    }

    public function update(UpdateRequest $request, string $id)
    {
        Karyawan::findOrFail($id)->update($request->validated());

        return redirect()->route('karyawans.index')->with('success', 'Karyawan berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $karyawan = Karyawan::findOrFail($id);

        // Menghapus karyawan akan menghapus berantai baris detail penggajian
        // beserta buku besar potongannya. Riwayat penggajian yang sudah dibayar
        // tidak boleh hilang — uangnya sudah berpindah.
        $adaPenggajianTerbayar = PenggajianDetail::where('karyawan_id', $karyawan->id)
            ->whereHas('penggajian', fn ($query) => $query->where('status', StatusPenggajian::Dibayar))
            ->exists();

        if ($adaPenggajianTerbayar) {
            return redirect()->route('karyawans.index')->with(
                'error',
                'Karyawan ini memiliki riwayat penggajian yang sudah dibayar dan tidak dapat dihapus.'
            );
        }

        /*
        | Buku besar potongan dilepas lebih dahulu, di dalam transaksi.
        |
        | cashbons.karyawan_id memakai cascade sedangkan
        | cashbon_potongans.cashbon_id memakai restrict, sehingga menghapus
        | karyawan yang cashbonnya masih dipesan pada penggajian belum dibayar
        | menabrak batasan itu dan berakhir sebagai galat SQL mentah. Seluruh
        | pemesanan yang tersisa pasti berada pada penggajian yang belum
        | dibayar — riwayat terbayar sudah ditolak di atas.
        */
        DB::transaction(function () use ($karyawan): void {
            CashbonPotongan::whereIn(
                'cashbon_id',
                Cashbon::where('karyawan_id', $karyawan->id)->select('id')
            )->delete();

            $karyawan->delete();
        });

        return redirect()->route('karyawans.index')->with('success', 'Karyawan berhasil dihapus.');
    }
}
