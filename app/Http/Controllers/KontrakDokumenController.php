<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dokumen\StoreRequest;
use App\Http\Requests\Dokumen\UpdateRequest;
use App\Models\Kontrak;
use App\Models\KontrakDokumen;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class KontrakDokumenController extends Controller implements HasMiddleware
{

     public static function middleware()
    {
        return [
            new Middleware('permission:kontraks dokumens index', only: ['index']),
            new Middleware('permission:kontraks dokumens create', only: ['create', 'store']),
            new Middleware('permission:kontraks dokumens edit', only: ['edit', 'update   ']),
            new Middleware('permission:kontraks dokumens delete', only: ['destroy']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index(string $kontrak_id, Request $request)
    {
        $dokumens = KontrakDokumen::when($request->search, function ($query, $search) {
            $query->where('nama_dokumen', 'like', "%{$search}%")
                ->orWhere('file', 'like', "%{$search}%");
        })
            ->where('kontrak_id', $kontrak_id)
            ->paginate(8)
            ->withQueryString();

        return inertia('kontraks/dokumens/index', [
            'dokumens' => $dokumens,
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
        return Inertia::render('kontraks/dokumens/create', [
            'kontrak_id' => $kontrak_id,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(string $kontrak_id, StoreRequest $request)
    {
        $kontrak = Kontrak::find($kontrak_id);
        $validated = $request->validated();

        if ($request->hasFile('file')) {
            $file = $request->file('file');

            $filename = time() . '_' . $file->getClientOriginalName();

            $filePath = $file->storeAs(
                "dokumens/kontrak-{$kontrak_id}",
                $filename,
                'public'
            );
        }

        // Tambahkan path file ke validated data
        $validated['file'] = $filePath;
        $validated['kontrak_id'] = $kontrak_id;

        // Buat record di database
        KontrakDokumen::create($validated);

        return redirect()->route('kontraks.dokumens.index', $kontrak_id)->with('success', 'Dokumen berhasil disimpan.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $kontrak_id, string $dokumen_id)
    {
        $dokumen = KontrakDokumen::find($dokumen_id);
        return Inertia::render('kontraks/dokumens/edit', [
            'dokumen' => $dokumen,
            'kontrak_id' => $kontrak_id,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(string $kontrak_id, string $dokumen_id, UpdateRequest $request)
    {
        $dokumen = KontrakDokumen::find($dokumen_id);
        $validated = $request->validated();

        // Jika ada file baru yang diupload
        if ($request->hasFile('file')) {
            $file = $request->file('file');

            // Hapus file lama dari storage
            if ($dokumen->file) {
                Storage::disk('public')->delete($dokumen->file);
            }

            // Simpan file baru
            $filename = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs(
                "dokumens/kontrak-{$kontrak_id}",
                $filename,
                'public'
            );

            $validated['file'] = $filePath;
        }

        // Update hanya field yang dikirim
        $dokumen->update($validated);

        return redirect()->route('kontraks.dokumens.index', $kontrak_id)->with('success', 'Dokumen berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $kontrak_id, string $dokumen_id)
    {
        $dokumen = KontrakDokumen::find($dokumen_id);
        if ($dokumen) {
            // Hapus file dari storage
            Storage::disk('public')->delete($dokumen->file);

            // Hapus record dari database
            $dokumen->delete();
        }

        return redirect()->route('kontraks.dokumens.index', $kontrak_id)->with('success', 'Dokumen berhasil dihapus.');
    }
}