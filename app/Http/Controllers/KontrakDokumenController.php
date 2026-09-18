<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dokumen\StoreRequest;
use App\Http\Requests\Dokumen\UpdateRequest;
use App\Models\Kontrak;
use App\Models\KontrakDokumen;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class KontrakDokumenController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('permission:kontraks dokumens index', only: ['index']),
            new Middleware('permission:kontraks dokumens create', only: ['create', 'store']),
            new Middleware('permission:kontraks dokumens edit', only: ['edit', 'update']),
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
            ->withQueryString()
            ->through(fn (KontrakDokumen $dokumen): array => [
                'id' => $dokumen->id,
                'kontrak_id' => $dokumen->kontrak_id,
                'nama_dokumen' => $dokumen->nama_dokumen,
                'file' => $dokumen->file,
                'diunggah' => $dokumen->created_at?->format('Y-m-d'),
            ]);

        $kontrak = Kontrak::findOrFail($kontrak_id);

        return inertia('kontraks/dokumens/index', [
            'dokumens' => $dokumens,
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
        return Inertia::render('kontraks/dokumens/create', [
            'kontrak_id' => $kontrak_id,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(string $kontrak_id, StoreRequest $request)
    {
        $kontrak = Kontrak::findOrFail($kontrak_id);
        $validated = $request->validated();

        $file = $request->file('file');

        $validated['file'] = $file->storeAs(
            "dokumens/kontrak-{$kontrak->id}",
            time().'_'.$file->getClientOriginalName(),
            'public'
        );
        $validated['kontrak_id'] = $kontrak->id;

        KontrakDokumen::create($validated);

        return redirect()->route('kontraks.dokumens.index', $kontrak_id)->with('success', 'Dokumen berhasil disimpan.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $kontrak_id, string $dokumen_id)
    {
        // findOrFail, bukan find: id yang tidak ada sebelumnya melanjutkan
        // dengan null dan berujung galat properti pada objek null.
        $dokumen = KontrakDokumen::where('kontrak_id', $kontrak_id)->findOrFail($dokumen_id);

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
        $dokumen = KontrakDokumen::where('kontrak_id', $kontrak_id)->findOrFail($dokumen_id);
        $validated = $request->validated();

        // Bidang berkas yang dikirim kosong tetap hadir sebagai null pada
        // kiriman multipart. Dibiarkan lewat, null itu akan menimpa path berkas
        // yang sudah ada dan dokumennya kehilangan lampiran.
        unset($validated['file']);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $berkasLama = $dokumen->file;

            // Berkas baru disimpan lebih dahulu; berkas lama baru dibuang
            // setelah penggantinya benar-benar ada.
            $validated['file'] = $file->storeAs(
                "dokumens/kontrak-{$kontrak_id}",
                time().'_'.$file->getClientOriginalName(),
                'public'
            );

            if ($berkasLama) {
                Storage::disk('public')->delete($berkasLama);
            }
        }

        $dokumen->update($validated);

        return redirect()->route('kontraks.dokumens.index', $kontrak_id)->with('success', 'Dokumen berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $kontrak_id, string $dokumen_id)
    {
        $dokumen = KontrakDokumen::where('kontrak_id', $kontrak_id)->findOrFail($dokumen_id);

        Storage::disk('public')->delete($dokumen->file);
        $dokumen->delete();

        return redirect()->route('kontraks.dokumens.index', $kontrak_id)->with('success', 'Dokumen berhasil dihapus.');
    }
}
