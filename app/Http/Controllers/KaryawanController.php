<?php

namespace App\Http\Controllers;

use App\Http\Requests\Karyawan\StoreRequest;
use App\Http\Requests\Karyawan\UpdateRequest;
use App\Models\Jabatan;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KaryawanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
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
        $jabatans = Jabatan::get();
        return Inertia::render('karyawans/create', [
            'jabatans' => $jabatans
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        Karyawan::create($request->validated());

        return redirect()->route("karyawans.index")->with("success", "Karyawan created successfully");
    }

    /**
     * Display the specified resource.
     */
    public function show(Karyawan $karyawan)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $karyawan = Karyawan::findOrFail($id);
        $jabatans = Jabatan::get();
        return Inertia::render('karyawans/edit', [
            'karyawan' => $karyawan,
            'jabatans' => $jabatans
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, string $id)
    {
        $karyawan = Karyawan::findOrFail($id);
        $karyawan->update($request->validated());

        return redirect()->route("karyawans.index")->with("success", "Karyawan updated successfully");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $karyawan = Karyawan::findOrFail($id);
        $karyawan->delete();

        return redirect()->route("karyawans.index")->with("success", "Karyawan deleted successfully");
    }
}
