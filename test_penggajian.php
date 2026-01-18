<?php

// Quick debug script to test payroll generation
// Run with: php artisan tinker < test_penggajian.php
// Or copy-paste the content below into tinker

use App\Models\Kontrak;
use App\Models\Penggajian;
use App\Services\PenggajianService;

$kontrak_id = 1; // Change this to your contract ID

// Get the contract with all relations
$kontrak = Kontrak::with('kontrakKaryawans.karyawan.jabatan', 'kontrakKaryawans.karyawan.cashbons')->find($kontrak_id);

if (!$kontrak) {
    echo "Contract not found!\n";
    exit;
}

echo "Contract ID: {$kontrak->id}\n";
echo "Tanggal Gajian: {$kontrak->tanggal_gajian}\n";
echo "Employee Count: " . $kontrak->kontrakKaryawans->count() . "\n\n";

// Check employee details
foreach ($kontrak->kontrakKaryawans as $kk) {
    $karyawan = $kk->karyawan;
    echo "Employee: {$karyawan->nama_karyawan}\n";
    echo "  - Jabatan: {$karyawan->jabatan?->nama_jabatan}\n";
    echo "  - Gaji (from jabatan): {$karyawan->jabatan?->gaji}\n";
    echo "  - BPJS (from jabatan): {$karyawan->jabatan?->bpjs}\n";
    echo "  - Cashbons belum_dibayar: ";
    
    if ($karyawan->relationLoaded('cashbons')) {
        $cashbon_total = $karyawan->cashbons
            ->where('status', 'belum_dibayar')
            ->sum('jumlah');
        echo "{$cashbon_total}\n";
    } else {
        $cashbon_total = $karyawan->cashbons()
            ->where('status', 'belum_dibayar')
            ->sum('jumlah');
        echo "{$cashbon_total}\n";
    }
}

echo "\nNow testing payroll generation...\n";

try {
    $service = new PenggajianService();
    $result = $service->generate($kontrak);
    
    echo "Generation Result:\n";
    echo "  - Success: " . ($result['success'] ? 'true' : 'false') . "\n";
    echo "  - Message: {$result['message']}\n";
    echo "  - Created periods: {$result['created_count']}\n";
    
    // Check penggajian details
    echo "\nPenggajian Details Created:\n";
    $penggajians = Penggajian::where('kontrak_id', $kontrak_id)->get();
    
    foreach ($penggajians as $penggajian) {
        echo "Periode: {$penggajian->periode}\n";
        foreach ($penggajian->penggajianDetails as $detail) {
            echo "  - {$detail->karyawan->nama_karyawan}: gaji_pokok={$detail->gaji_pokok}, bpjs={$detail->bpjs}, potongan={$detail->potongan_cashbon}, total={$detail->total_gaji}\n";
        }
    }
} catch (Exception $e) {
    echo "Error: {$e->getMessage()}\n";
    echo "Stack: {$e->getTraceAsString()}\n";
}
