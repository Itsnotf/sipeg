# Bug Fix: Cashbon Status Update When Penggajian Paid

## 🐛 Bug yang Ditemukan

Ketika penggajian dibayar (status berubah menjadi 'dibayar'), cashbon yang sudah dipotong masih tetap status 'belum_dibayar'. Seharusnya cashbon juga berubah menjadi 'dibayar'.

### Skenario Bug:
```
1. Admin generate penggajian untuk Siti
   - Gaji: 8M, BPJS: 400k, Cashbon Potongan: 2M
   - Total: 5.6M

2. Siti membuat cashbon: 2M (status: BELUM_DIBAYAR)
   - Observer trigger → cashbon otomatis dipotong ✓

3. Admin bayar penggajian (status: DIBAYAR)
   - Penggajian: DIBAYAR ✓
   - Cashbon: BELUM_DIBAYAR ❌ (MASALAH)
```

## ✅ Solusi: PenggajianObserver

**File Baru**: `/app/Observers/PenggajianObserver.php`

### Logic:
```php
public function updated(Penggajian $penggajian): void {
    // Ketika status berubah menjadi 'dibayar'
    if ($penggajian->isDirty('status') && $penggajian->status === 'dibayar') {
        $this->markCashbonsAsPaid($penggajian);
    }
}
```

### Flow:
1. Detect status change ke 'dibayar'
2. Get semua penggajian_details yang ada potongan_cashbon
3. Cari cashbons milik karyawan tersebut yang status 'belum_dibayar'
4. Match amount: jika cashbon amount ≤ potongan_cashbon
5. Update cashbon status → 'dibayar'

## 📋 Implementation Details

### 1. PenggajianObserver
```php
// app/Observers/PenggajianObserver.php

Handle when Penggajian.status updated:
├─ Check if status changed to 'dibayar'
├─ Get all penggajian_details with potongan_cashbon > 0
└─ For each detail:
   ├─ Find karyawan's cashbons with status 'belum_dibayar'
   ├─ Match amount: cashbon.jumlah <= potongan_cashbon
   └─ Update cashbon.status = 'dibayar'
```

### 2. Registration in AppServiceProvider
```php
Penggajian::observe(PenggajianObserver::class);
```

### 3. Transactions
- Wrapped in DB::transaction() untuk consistency
- Error handling dengan logging
- Non-blocking: jika ada error, tidak crash aplikasi

## 🧪 Test Case

**Test**: `when penggajian is paid, related cashbons should be marked as paid`

```php
test('when penggajian is paid, related cashbons should be marked as paid', function () {
    // 1. Generate penggajian
    $service->generate($kontrak);
    
    // 2. Create cashbon (belum_dibayar)
    $cashbon = $karyawan->cashbons()->create([
        'jumlah' => 1500000,
        'status' => 'belum_dibayar',
    ]);
    
    // 3. Verify cashbon belum_dibayar
    expect($cashbon->fresh()->status)->toBe('belum_dibayar');
    
    // 4. Verify penggajian detail has potongan
    expect($detail->potongan_cashbon)->toEqual(1500000.00);
    
    // 5. Update penggajian status to dibayar
    $penggajian->update(['status' => 'dibayar']);
    
    // 6. Verify cashbon automatically marked as dibayar ✓
    expect($cashbon->fresh()->status)->toBe('dibayar');
});
```

✅ **Status**: PASS

## 🔄 Complete Cashbon Lifecycle

```
┌──────────────────────────────────────────────────┐
│ Cashbon Lifecycle (Complete Flow)                │
└──────────────────┬───────────────────────────────┘
                   │
        ┌──────────▼──────────┐
        │ 1. CREATE CASHBON   │
        │ Status: BELUM_DIBAYAR
        └──────────┬──────────┘
                   │
             ┌─────▼──────────────────────────┐
             │ CashbonObserver.created()      │
             │ ├─ Find penggajian terdekat    │
             │ ├─ Update potongan_cashbon     │
             │ └─ Recalc total_gaji           │
             └─────┬──────────────────────────┘
                   │
        ┌──────────▼──────────────┐
        │ 2. PENGGAJIAN CREATED   │
        │ Detail.potongan_cashbon │
        │ set from observer       │
        └──────────┬──────────────┘
                   │
        ┌──────────▼──────────────────┐
        │ 3. ADMIN MARKS PAID         │
        │ Penggajian.status = dibayar │
        └──────────┬──────────────────┘
                   │
        ┌──────────▼──────────────────────────┐
        │ PenggajianObserver.updated()        │
        │ ├─ Detect status change → dibayar   │
        │ ├─ Get related cashbons             │
        │ ├─ Match amount                     │
        │ └─ Update cashbon.status = dibayar  │
        └──────────┬──────────────────────────┘
                   │
        ┌──────────▼────────────────────┐
        │ 4. CASHBON PAID               │
        │ Status: DIBAYAR               │
        │ Lifecycle Complete ✓          │
        └───────────────────────────────┘
```

## 📊 Test Results

```
✓ payroll generation creates penggajian with correct gaji_pokok
✓ payroll generation prevents duplicate periodes for same kontrak
✓ payroll generation includes cashbon deductions
✓ validates kontrak has valid tanggal_gajian
✓ cashbon created after penggajian is automatically applied
✓ when penggajian is paid, related cashbons should be marked as paid

Tests: 6 passed (24 assertions)
```

## 🎯 Key Features

1. ✅ **Automatic Status Update** - Cashbon auto → dibayar saat penggajian dibayar
2. ✅ **Amount Matching** - Only mark paid if cashbon amount ≤ deduction
3. ✅ **Multiple Cashbons** - Handle multiple cashbons per employee
4. ✅ **Transactional** - All or nothing update
5. ✅ **Logged** - Track all status changes
6. ✅ **Safe** - Error handling, non-blocking

## 💡 Edge Cases Handled

| Skenario | Behavior |
|----------|----------|
| Single cashbon | Marked as dibayar ✓ |
| Multiple cashbons | All matching ones marked ✓ |
| Partial deduction | Only matching amount marked |
| No cashbons | No change (skip) ✓ |
| Already paid | Idempotent (no change) ✓ |
| Cashbon > deduction | Not marked (partial) ✓ |

## 🚀 Usage

### Normal Flow:
```bash
# 1. Generate penggajian (with or without cashbon)
php artisan penggajian:generate --kontrak_id=1

# 2. Create cashbon (observer auto deducts)
POST /api/cashbons
{
    "karyawan_id": 1,
    "jumlah": 2000000,
    "status": "belum_dibayar"
}

# 3. Mark penggajian as paid (observer auto mark cashbon)
PUT /api/penggajians/1
{
    "status": "dibayar"
}

# 4. Verify cashbon status updated
GET /api/cashbons/1
Response: status = "dibayar" ✓
```

## 📝 Files Modified

- ✅ `/app/Observers/PenggajianObserver.php` - NEW
- ✅ `/app/Providers/AppServiceProvider.php` - UPDATED
- ✅ `/tests/Feature/PenggajianGenerationTest.php` - UPDATED (new test added)

---

**Status**: ✅ FIXED  
**Test**: 6/6 PASS  
**Bug**: Resolved
