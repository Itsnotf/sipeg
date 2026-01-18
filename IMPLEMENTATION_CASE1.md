# Case 1: Cashbon Setelah Penggajian Di-Generate

## 📋 Deskripsi
Ketika karyawan membuat cashbon **setelah penggajian sudah di-generate**, cashbon ini secara otomatis dipotong dari **penggajian terdekat yang belum dibayar**.

## 🎯 Skenario
```
1. Admin generate penggajian Januari untuk karyawan X
   - Penggajian detail: Gaji = 10M, BPJS = 500k, Cashbon = 0, Total = 9.5M

2. Karyawan X membuat cashbon baru: 2M dengan status "belum_dibayar"

3. Sistem OTOMATIS update penggajian terdekat:
   - Penggajian detail: Gaji = 10M, BPJS = 500k, Cashbon = 2M, Total = 7.5M
   - Total penggajian di-update dari 9.5M → 7.5M
```

## 🔧 Implementasi

### 1. Observer: CashbonObserver (app/Observers/CashbonObserver.php)

**File Baru Dibuat**: `/app/Observers/CashbonObserver.php`

Observer ini mendengarkan event pada Cashbon model:

```php
public function created(Cashbon $cashbon): void {
    // Saat cashbon baru dengan status 'belum_dibayar' dibuat
    if ($cashbon->status === 'belum_dibayar') {
        $this->applyToPenggajian($cashbon);
    }
}
```

**Logika `applyToPenggajian`**:
- Cari semua kontrak yang terkait dengan karyawan
- Cari penggajian **terdekat** dengan status `belum_dibayar` (periode terbaru)
- **Tambah** potongan_cashbon di penggajian_details karyawan tersebut
- **Recalculate** total_gaji: `gaji_pokok - bpjs - potongan_cashbon`
- **Update** total_gaji penggajian

**Logika `removeFromPenggajian`** (saat cashbon dihapus/dibayar):
- Cari penggajian_details yang memiliki potongan dari cashbon ini
- **Kurangi** potongan_cashbon
- **Recalculate** total_gaji
- **Update** total penggajian

### 2. Registrasi Observer (app/Providers/AppServiceProvider.php)

```php
public function boot(): void {
    Cashbon::observe(CashbonObserver::class);
}
```

### 3. Database Migration

**File Baru**: `database/migrations/2026_01_06_114626_add_total_gaji_to_penggajians_table.php`

Menambahkan field `total_gaji` ke tabel penggajians untuk tracking total gaji per periode:

```php
$table->decimal('total_gaji', 15, 2)->default(0);
```

### 4. Update PenggajianService

Saat generate penggajian, sekarang juga **calculate dan set total_gaji**:

```php
$penggajian = Penggajian::create([...]);
$this->createPenggajianDetails($penggajian, $karyawans);

// Calculate total setelah semua details dibuat
$totalGaji = $penggajian->penggajianDetails()->sum('total_gaji');
$penggajian->update(['total_gaji' => $totalGaji]);
```

## 🧪 Test Coverage

**Test Baru**: `tests/Feature/PenggajianGenerationTest.php::cashbon_created_after_penggajian_is_automatically_applied_to_next_pending_penggajian`

```php
test('cashbon created after penggajian is automatically applied to next pending penggajian', function () {
    // 1. Generate penggajian
    // 2. Verify initial total (9.5M)
    // 3. Create cashbon (2M) after generate
    // 4. Verify total updated automatically (7.5M)
    // 5. Verify penggajian total updated
});
```

✅ **Status**: PASS

## 📊 Perhitungan Formula

```
Total Gaji = Gaji Pokok - BPJS - Potongan Cashbon

Contoh:
- Gaji Pokok: 10,000,000
- BPJS: 500,000
- Potongan Cashbon: 2,000,000 (created after generate)
─────────────────────────────
Total Gaji: 7,500,000
```

## 🔄 Event Flow

```
┌─────────────────────────────────────────────┐
│ Cashbon Created/Updated/Deleted             │
└──────────────┬──────────────────────────────┘
               │
               ▼
        ┌──────────────────┐
        │ CashbonObserver  │
        └──────────┬───────┘
                   │
        ┌──────────┴──────────┐
        │                     │
        ▼                     ▼
   ┌─────────────┐   ┌──────────────────┐
   │   Apply     │   │     Remove       │
   │ to Penggajian   │ from Penggajian │
   └──────┬──────┘   └────────┬─────────┘
          │                   │
          ▼                   ▼
   ┌──────────────────────────────────┐
   │ Update penggajian_details        │
   │ - potongan_cashbon += jumlah     │
   │ - total_gaji recalculated        │
   │ - penggajian.total_gaji updated  │
   └──────────────────────────────────┘
```

## 🎓 Key Points

1. **Automatic Deduction**: Tidak perlu admin manual update penggajian
2. **Smart Selection**: Cashbon selalu dipotong dari penggajian **terdekat** yang belum dibayar
3. **Atomic Transaction**: Semua update dalam satu transaction (DB::transaction)
4. **Idempotent**: Jika cashbon di-update status-nya, observer akan adjust potongan secara otomatis
5. **Error Handling**: Error di-log, tidak crash aplikasi

## ✅ Validasi Berhasil

```
✓ payroll generation creates penggajian with correct gaji_pokok
✓ payroll generation prevents duplicate periodes for same kontrak
✓ payroll generation includes cashbon deductions
✓ validates kontrak has valid tanggal_gajian
✓ cashbon created after penggajian is automatically applied to next pending penggajian

Tests: 5 passed (20 assertions)
```

---

**Status**: ✅ COMPLETED
