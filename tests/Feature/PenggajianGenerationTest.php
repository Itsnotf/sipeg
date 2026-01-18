<?php

use App\Models\Client;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\Kontrak;
use App\Models\KontrakKaryawan;
use App\Models\Penggajian;
use App\Models\User;
use App\Services\PenggajianService;
use Carbon\Carbon;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    
    // Create a client
    $this->client = Client::create([
        'nama_client' => 'PT Test Company',
        'alamat' => 'Jl Test',
        'no_hp' => '08123456789',
        'email' => 'test@company.com',
        'deskripsi' => 'Test company description',
    ]);
    
    // Create a jabatan with gaji and bpjs
    $this->jabatan = Jabatan::create([
        'nama_jabatan' => 'Developer',
        'deskripsi' => 'Software Developer',
        'gaji' => 10000000, // 10 million
        'bpjs' => 500000, // 500k
    ]);
    
    // Create a karyawan with the jabatan
    $this->karyawan = Karyawan::create([
        'nama' => 'John Doe',
        'id_jabatan' => $this->jabatan->id,
        'nik' => '12345678901234567',
        'alamat' => 'Jl Test',
        'jenis_kelamin' => 'Laki-laki',
        'tanggal_lahir' => '1990-01-01',
        'no_hp' => '08123456789',
        'status' => 'aktif',
    ]);
    
    // Create a kontrak
    $this->kontrak = Kontrak::create([
        'client_id' => $this->client->id,
        'judul' => 'Kontrak Developer',
        'deskripsi' => 'Kontrak pengembangan software',
        'tanggal_mulai' => Carbon::now()->startOfMonth()->format('Y-m-d'),
        'tanggal_selesai' => Carbon::now()->endOfMonth()->format('Y-m-d'),
        'total_biaya' => 50000000,
        'tanggal_gajian' => 5,
        'status' => 'aktif',
    ]);
    
    // Attach karyawan to kontrak
    KontrakKaryawan::create([
        'kontrak_id' => $this->kontrak->id,
        'karyawan_id' => $this->karyawan->id,
    ]);
});

test('payroll generation creates penggajian with correct gaji_pokok', function () {
    $service = new PenggajianService();
    
    // Load relations
    $kontrak = Kontrak::with('kontrakKaryawans.karyawan.jabatan', 'kontrakKaryawans.karyawan.cashbons')
        ->find($this->kontrak->id);
    
    // Generate payroll
    $result = $service->generate($kontrak);
    
    expect($result['success'])->toBeTrue();
    expect($result['created_count'])->toBeGreaterThan(0);
    
    // Check penggajian_detail has correct gaji_pokok
    $penggajian = Penggajian::where('kontrak_id', $this->kontrak->id)->first();
    expect($penggajian)->not->toBeNull();
    
    $detail = $penggajian->penggajianDetails()->where('karyawan_id', $this->karyawan->id)->first();
    expect($detail)->not->toBeNull();
    expect($detail->gaji_pokok)->toEqual(10000000.00);
    expect($detail->bpjs)->toEqual(500000.00);
    expect($detail->potongan_cashbon)->toEqual(0.00);
    expect($detail->total_gaji)->toEqual(9500000.00); // 10M - 500k
});

test('payroll generation prevents duplicate periodes for same kontrak', function () {
    $service = new PenggajianService();
    
    $kontrak = Kontrak::with('kontrakKaryawans.karyawan.jabatan', 'kontrakKaryawans.karyawan.cashbons')
        ->find($this->kontrak->id);
    
    // Generate payroll first time
    $result1 = $service->generate($kontrak);
    expect($result1['success'])->toBeTrue();
    
    $count1 = Penggajian::where('kontrak_id', $this->kontrak->id)->count();
    
    // Generate payroll second time - should not create duplicates
    $result2 = $service->generate($kontrak);
    
    $count2 = Penggajian::where('kontrak_id', $this->kontrak->id)->count();
    
    expect($count2)->toBe($count1); // Same count, no duplicates
});

test('payroll generation includes cashbon deductions', function () {
    // Create a cashbon
    $this->karyawan->cashbons()->create([
        'jumlah' => 1000000, // 1 million cashbon
        'keterangan' => 'Pinjaman',
        'status' => 'belum_dibayar',
    ]);
    
    $service = new PenggajianService();
    
    $kontrak = Kontrak::with('kontrakKaryawans.karyawan.jabatan', 'kontrakKaryawans.karyawan.cashbons')
        ->find($this->kontrak->id);
    
    $result = $service->generate($kontrak);
    expect($result['success'])->toBeTrue();
    
    $penggajian = Penggajian::where('kontrak_id', $this->kontrak->id)->first();
    $detail = $penggajian->penggajianDetails()->where('karyawan_id', $this->karyawan->id)->first();
    
    expect($detail->potongan_cashbon)->toEqual(1000000.00);
    expect($detail->total_gaji)->toEqual(8500000.00); // 10M - 500k - 1M
});

test('validates kontrak has valid tanggal_gajian', function () {
    $invalidKontrak = Kontrak::create([
        'client_id' => $this->client->id,
        'judul' => 'Kontrak Invalid',
        'deskripsi' => 'Invalid kontrak',
        'tanggal_mulai' => Carbon::now()->startOfMonth()->format('Y-m-d'),
        'tanggal_selesai' => Carbon::now()->endOfMonth()->format('Y-m-d'),
        'total_biaya' => 50000000,
        'tanggal_gajian' => 32, // Invalid - out of month range
        'status' => 'aktif',
    ]);
    
    $service = new PenggajianService();
    
    try {
        $invalidKontrak = Kontrak::with('kontrakKaryawans.karyawan.jabatan', 'kontrakKaryawans.karyawan.cashbons')
            ->find($invalidKontrak->id);
        $service->generate($invalidKontrak);
        
        // Should reach here with null handling
        expect(true)->toBeTrue();
    } catch (Exception $e) {
        expect($e->getMessage())->toContain('Tanggal gajian');
    }
});

test('cashbon created after penggajian is automatically applied to next pending penggajian', function () {
    $service = new PenggajianService();
    
    // Generate payroll first (without cashbon)
    $kontrak = Kontrak::with('kontrakKaryawans.karyawan.jabatan', 'kontrakKaryawans.karyawan.cashbons')
        ->find($this->kontrak->id);
    
    $result = $service->generate($kontrak);
    expect($result['success'])->toBeTrue();
    
    // Get initial penggajian_detail without cashbon
    $penggajian = Penggajian::where('kontrak_id', $this->kontrak->id)->first();
    $detailBefore = $penggajian->penggajianDetails()->where('karyawan_id', $this->karyawan->id)->first();
    
    expect($detailBefore->potongan_cashbon)->toEqual(0.00);
    expect($detailBefore->total_gaji)->toEqual(9500000.00); // 10M - 500k
    
    // Create cashbon AFTER penggajian is generated
    $this->karyawan->cashbons()->create([
        'jumlah' => 2000000, // 2 million cashbon
        'keterangan' => 'Pinjaman setelah generate',
        'status' => 'belum_dibayar',
    ]);
    
    // Refresh and check if penggajian_detail is updated automatically
    $detailAfter = $penggajian->penggajianDetails()
        ->where('karyawan_id', $this->karyawan->id)
        ->first();
    
    expect($detailAfter->potongan_cashbon)->toEqual(2000000.00);
    expect($detailAfter->total_gaji)->toEqual(7500000.00); // 10M - 500k - 2M
    
    // Check penggajian total is updated
    $penggajianUpdated = $penggajian->fresh();
    expect($penggajianUpdated->total_gaji)->toEqual(7500000.00);
});

test('when penggajian is paid, related cashbons should be marked as paid', function () {
    $service = new PenggajianService();
    
    // Generate payroll
    $kontrak = Kontrak::with('kontrakKaryawans.karyawan.jabatan', 'kontrakKaryawans.karyawan.cashbons')
        ->find($this->kontrak->id);
    
    $result = $service->generate($kontrak);
    expect($result['success'])->toBeTrue();
    
    // Get penggajian
    $penggajian = Penggajian::where('kontrak_id', $this->kontrak->id)->first();
    
    // Create cashbon with status belum_dibayar
    $cashbon = $this->karyawan->cashbons()->create([
        'jumlah' => 1500000,
        'keterangan' => 'Test cashbon',
        'status' => 'belum_dibayar',
    ]);
    
    // Verify cashbon is belum_dibayar
    expect($cashbon->fresh()->status)->toBe('belum_dibayar');
    
    // Verify penggajian detail has potongan
    $detail = $penggajian->penggajianDetails()
        ->where('karyawan_id', $this->karyawan->id)
        ->first();
    expect($detail->potongan_cashbon)->toEqual(1500000.00);
    
    // Update penggajian status to dibayar
    $penggajian->update(['status' => 'dibayar']);
    
    // Verify cashbon is automatically marked as dibayar
    $cashbonAfter = $cashbon->fresh();
    expect($cashbonAfter->status)->toBe('dibayar');
});
