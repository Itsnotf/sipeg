<?php

namespace App\Http\Controllers;

use App\Http\Requests\Jabatan\StoreRequest;
use App\Http\Requests\Jabatan\UpdateRequest;
use App\Models\Jabatan;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class JabatanController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('permission:jabatans index', only: ['index']),
            new Middleware('permission:jabatans create', only: ['create', 'store']),
            new Middleware('permission:jabatans edit', only: ['edit', 'update']),
            new Middleware('permission:jabatans delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $Jabatans = Jabatan::when($request->search, function ($query, $search) {
            $query->where('nama_jabatan', 'like', "%{$search}%")
                ->orWhere('deskripsi', 'like', "%{$search}%");
        })
            ->paginate(8)
            ->withQueryString();

        return inertia('jabatans/index', [
            'jabatans' => $Jabatans,
            'filters' => $request->only('search'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('jabatans/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        Jabatan::create($request->validated());

        return redirect()->route('jabatans.index')->with('success', 'Jabatan berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $jabatan = Jabatan::findOrFail($id);

        return Inertia::render('jabatans/edit', [
            'jabatan' => $jabatan,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, string $id)
    {
        $jabatan = Jabatan::findOrFail($id);

        $jabatan->update($request->validated());

        return redirect()->route('jabatans.index')->with('success', 'Jabatan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $jabatan = Jabatan::findOrFail($id);

        /*
        | Kunci pengaman, bukan sekadar kenyamanan.
        |
        | karyawans.id_jabatan memakai cascade, sehingga menghapus jabatan akan
        | ikut menghapus seluruh karyawannya — dan berantai ke detail
        | penggajian serta cashbon mereka. Riwayat gaji yang sudah dibayarkan
        | lenyap tanpa satu pun peringatan; kalaupun buku besar potongan
        | menahannya, yang muncul hanyalah galat SQL.
        */
        $jumlahKaryawan = Karyawan::where('id_jabatan', $jabatan->id)->count();

        if ($jumlahKaryawan > 0) {
            return redirect()->route('jabatans.index')->with(
                'error',
                "Jabatan ini masih dipakai {$jumlahKaryawan} karyawan. Pindahkan mereka ke jabatan lain lebih dahulu."
            );
        }

        $jabatan->delete();

        return redirect()->route('jabatans.index')->with('success', 'Jabatan berhasil dihapus.');
    }
}
