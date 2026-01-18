# Database Seeder Documentation

## 🌱 Comprehensive Application Data Seeder

### Deskripsi
`AppDataSeeder` adalah seeder comprehensive yang menjalankan seluruh flow aplikasi dengan data sample yang realistic. Seeder ini membuat:

- **5 Jabatan** (Positions) dengan gaji dan BPJS
- **6 Karyawan** (Employees) dengan berbagai jabatan
- **3 Klien** (Clients) 
- **4 Kontrak** (Contracts) dengan berbagai status
- **11 Relasi** Karyawan-Kontrak
- **8 Penggajian** (Payroll) dengan automatic generation
- **24 Detail Penggajian** dengan perhitungan gaji otomatis
- **5 Cashbon** dengan berbagai status

### 📊 Data Sample

#### Jabatan (Positions)
```
1. Senior Developer - Rp 15,000,000/bulan (BPJS: Rp 750,000)
2. Junior Developer - Rp 8,000,000/bulan (BPJS: Rp 400,000)
3. UI/UX Designer - Rp 10,000,000/bulan (BPJS: Rp 500,000)
4. Project Manager - Rp 12,000,000/bulan (BPJS: Rp 600,000)
5. QA Engineer - Rp 9,000,000/bulan (BPJS: Rp 450,000)
```

#### Karyawan (Employees)
```
1. Budi Hartono - Senior Developer (Male)
2. Siti Nurhaliza - Junior Developer (Female)
3. Adi Wijaya - Junior Developer (Male)
4. Dewi Lestari - UI/UX Designer (Female)
5. Raka Setiawan - Project Manager (Male)
6. Maya Putri - QA Engineer (Female)
```

#### Clients
```
1. PT Digital Indonesia - Digital transformation services
2. CV Teknologi Maju - Technology consulting
3. PT Startup Inovatif - Fast-growing startup
```

#### Kontraks (Contracts)
```
1. Website Revamp Project (AKTIF) 
   - Client: PT Digital Indonesia
   - Budget: Rp 150,000,000
   - Karyawan: Budi (Senior Dev), Siti (Junior Dev), Dewi (Designer)
   - Gajian Tgl: 5

2. Mobile App Development (AKTIF)
   - Client: CV Teknologi Maju
   - Budget: Rp 200,000,000
   - Karyawan: Budi (Senior Dev), Siti & Adi (Junior Dev), Maya (QA)
   - Gajian Tgl: 10

3. ERP System Implementation (SELESAI)
   - Client: PT Startup Inovatif
   - Budget: Rp 300,000,000
   - Karyawan: Budi (Senior Dev), Maya (QA), Raka (PM)
   - Gajian Tgl: 20

4. UI/UX Design Workshop (AKTIF)
   - Client: PT Digital Indonesia
   - Budget: Rp 50,000,000
   - Karyawan: Dewi (Designer)
   - Gajian Tgl: 15
```

### 💳 Cashbons Created
```
1. Budi Hartono - Rp 3,000,000 (DIBAYAR) - Keperluan darurat
2. Siti Nurhaliza - Rp 2,000,000 (BELUM_DIBAYAR) - Pinjaman pendidikan ⚠️
3. Adi Wijaya - Rp 5,000,000 (BELUM_DIBAYAR) - Cicilan rumah ⚠️
4. Dewi Lestari - Rp 1,500,000 (DIBAYAR) - Biaya kesehatan
5. Budi Hartono - Rp 2,500,000 (BELUM_DIBAYAR) - Kebutuhan keluarga ⚠️
```

**Note**: Cashbon dengan status `BELUM_DIBAYAR` akan otomatis dipotong dari penggajian terdekat (Case 1 Implementation).

### 📈 Penggajian Generated
```
Website Revamp Project:
  - Periode 1: 3 karyawan
  - Periode 2: 3 karyawan

Mobile App Development:
  - Periode 1: 4 karyawan
  - Periode 2: 4 karyawan

ERP System Implementation:
  - Periode 1: 3 karyawan
  - Periode 2: 3 karyawan
  - Periode 3: 3 karyawan

UI/UX Design Workshop:
  - Periode 1: 1 karyawan
```

**Total**: 8 Penggajian dengan 24 Detail Penggajian

### 🎯 Fitur yang Ditest

Seeder ini menjalankan seluruh flow aplikasi:

1. ✅ **Model Creation** - Semua model (Jabatan, Karyawan, Kontrak, etc.)
2. ✅ **Relationships** - Relasi Many-to-Many (KontrakKaryawan)
3. ✅ **Payroll Generation** - Auto generate penggajian & detail
4. ✅ **Cashbon Management** - Create cashbon dengan berbagai status
5. ✅ **Cashbon Deduction** - Auto deduction via Observer (Case 1)
6. ✅ **Status Tracking** - Kontrak dan penggajian dengan berbagai status
7. ✅ **Financial Calculation** - Gaji pokok, BPJS, cashbon deduction

### 🚀 Cara Menggunakan

#### 1. Fresh Seed (Reset Database + Seed)
```bash
php artisan migrate:fresh --seed
```

#### 2. Seed Only (Keep existing data)
```bash
php artisan db:seed --class=AppDataSeeder
```

#### 3. Seed Specific Class
```bash
php artisan db:seed --class=Database\\Seeders\\AppDataSeeder
```

### 📊 Output Example

```
🌱 Starting comprehensive application data seeding...
📋 Creating Jabatan (Positions)...
  ✓ Created: Senior Developer (Rp 15000000)
  ✓ Created: Junior Developer (Rp 8000000)
  ...

👥 Creating Karyawan (Employees)...
  ✓ Created: Budi Hartono
  ✓ Created: Siti Nurhaliza
  ...

🏢 Creating Clients...
  ✓ Created: PT Digital Indonesia
  ...

📄 Creating Kontraks (Contracts)...
  ✓ Created: Website Revamp Project (aktif) - 3 karyawan
  ...

💰 Generating Penggajian (Payroll)...
  ✓ Website Revamp Project: 2 penggajian generated
  ...

💳 Creating Cashbons...
  ✓ Cashbon Rp 3000000 untuk Budi Hartono (dibayar)
  ...

✅ Updating Penggajian status to dibayar...
  ✓ Updated: ERP System Implementation - Penggajian dibayar
  ...

✨ Database seeding completed successfully!

📊 Seeding Summary:
─────────────────────────────────────
  Clients: 3
  Jabatan: 5
  Karyawan: 6
  Kontrak: 4
  KontrakKaryawan: 11
  Penggajian: 8
  Penggajian Details: 24
  Cashbon: 5
─────────────────────────────────────

💰 Financial Summary:
─────────────────────────────────────
  Total Biaya Kontrak: Rp 700,000,000
  Total Penggajian: Rp 247,000,000
  Keuntungan Bersih: Rp 453,000,000
─────────────────────────────────────
```

### 🔄 Data Flow

```
┌─────────────────────────────────────────────────┐
│ AppDataSeeder                                   │
├─────────────────────────────────────────────────┤
│ 1. Create Jabatan (5 positions)                │
│ 2. Create Karyawan (6 employees)               │
│ 3. Create Clients (3 clients)                  │
│ 4. Create Kontraks + Attach Karyawans (4)     │
│ 5. Generate Penggajian (8 periods)             │
│    ├─ Create Penggajian record                 │
│    ├─ Create PenggajianDetails for each emp    │
│    └─ Calculate total_gaji                     │
│ 6. Create Cashbons (5 cashbons)                │
│    ├─ Some marked as DIBAYAR                   │
│    └─ Some marked as BELUM_DIBAYAR            │
│       └─ Trigger Observer → auto deduct ✓     │
│ 7. Mark some Penggajian as DIBAYAR             │
└─────────────────────────────────────────────────┘
```

### 🧪 Testing Scenarios

Dengan data dari seeder ini, user dapat test:

#### Scenario 1: View Dashboard
- Lihat statistics (3 clients, 6 karyawans, 8 penggajians)
- Lihat financial summary (Rp 700M biaya, Rp 247M penggajian)
- Lihat recent kontraks & penggajians

#### Scenario 2: View Kontrak Detail
- Lihat karyawan terattach (multiple scenarios)
- Lihat penggajian dengan status berbeda (DIBAYAR, BELUM_DIBAYAR)
- Lihat keuntungan/rugi calculation

#### Scenario 3: Cashbon Deduction
- Siti & Adi memiliki cashbon BELUM_DIBAYAR
- Lihat di penggajian detail, cashbon otomatis dipotong
- Total gaji = gaji_pokok - BPJS - cashbon_deduction

#### Scenario 4: Payment Status
- ERP Project sudah SELESAI & penggajian sudah DIBAYAR
- Website Project ada penggajian DIBAYAR & BELUM_DIBAYAR
- Mobile App Project semua BELUM_DIBAYAR

### ✅ Validasi Success Criteria

Seeder berhasil jika:
```
✓ Total 3 clients created
✓ Total 5 jabatan created
✓ Total 6 karyawan created
✓ Total 4 kontrak created (3 aktif, 1 selesai)
✓ Total 11 kontrak-karyawan relationships
✓ Total 8 penggajian generated
✓ Total 24 penggajian details created
✓ Total 5 cashbons created
✓ Cashbon deduction working (observer triggered)
✓ Penggajian totals calculated correctly
✓ Financial summary: Rp 700M biaya, Rp 247M penggajian
```

---

**Version**: 1.0  
**Last Updated**: 2026-01-06  
**Status**: ✅ WORKING
