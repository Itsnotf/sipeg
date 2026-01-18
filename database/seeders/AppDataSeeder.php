<?php

namespace Database\Seeders;

use App\Models\Cashbon;
use App\Models\Client;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\Kontrak;
use App\Models\KontrakKaryawan;
use App\Models\Penggajian;
use App\Models\PenggajianDetail;
use App\Services\PenggajianService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AppDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting comprehensive application data seeding...');

        // 1. Create Jabatan (Positions with salary & BPJS)
        $this->command->info('📋 Creating Jabatan (Positions)...');
        $jabatans = $this->createJabatans();

        // 2. Create Karyawan (Employees)
        $this->command->info('👥 Creating Karyawan (Employees)...');
        $karyawans = $this->createKaryawans($jabatans);

        // 3. Create Clients
        $this->command->info('🏢 Creating Clients...');
        $clients = $this->createClients();

        // 4. Create Kontraks with Karyawans
        $this->command->info('📄 Creating Kontraks (Contracts)...');
        $kontraks = $this->createKontraks($clients, $karyawans);

        // 5. Generate Penggajian for Kontraks
        $this->command->info('💰 Generating Penggajian (Payroll)...');
        $this->generatePenggajian($kontraks);

        // 6. Create Cashbons
        $this->command->info('💳 Creating Cashbons...');
        $this->createCashbons($karyawans);

        // 7. Mark some Penggajians as paid
        $this->command->info('✅ Updating Penggajian status to dibayar...');
        $this->markPenggajianPaid();

        $this->command->info('');
        $this->command->info('✨ Database seeding completed successfully!');
        $this->command->info('');
        $this->printSummary();
    }

    /**
     * Create Jabatan (Positions) with salary & BPJS
     */
    private function createJabatans(): array
    {
        $jabatans = [
            [
                'nama_jabatan' => 'Senior Developer',
                'deskripsi' => 'Experienced software developer',
                'gaji' => 15000000,
                'bpjs' => 750000,
            ],
            [
                'nama_jabatan' => 'Junior Developer',
                'deskripsi' => 'Entry level software developer',
                'gaji' => 8000000,
                'bpjs' => 400000,
            ],
            [
                'nama_jabatan' => 'UI/UX Designer',
                'deskripsi' => 'Product designer',
                'gaji' => 10000000,
                'bpjs' => 500000,
            ],
            [
                'nama_jabatan' => 'Project Manager',
                'deskripsi' => 'Project management',
                'gaji' => 12000000,
                'bpjs' => 600000,
            ],
            [
                'nama_jabatan' => 'QA Engineer',
                'deskripsi' => 'Quality assurance engineer',
                'gaji' => 9000000,
                'bpjs' => 450000,
            ],
        ];

        $created = [];
        foreach ($jabatans as $jabatan) {
            $created[] = Jabatan::create($jabatan);
            $this->command->line("  ✓ Created: {$jabatan['nama_jabatan']} (Rp {$jabatan['gaji']})");
        }

        return $created;
    }

    /**
     * Create Karyawan (Employees)
     */
    private function createKaryawans(array $jabatans): array
    {
        $karyawans = [
            [
                'nama' => 'Budi Hartono',
                'id_jabatan' => $jabatans[0]->id, // Senior Dev
                'nik' => '12345678901234501',
                'alamat' => 'Jl. Merdeka No. 1, Jakarta',
                'jenis_kelamin' => 'Laki-laki',
                'tanggal_lahir' => '1990-05-15',
                'no_hp' => '081234567890',
                'status' => 'aktif',
            ],
            [
                'nama' => 'Siti Nurhaliza',
                'id_jabatan' => $jabatans[1]->id, // Junior Dev
                'nik' => '12345678901234502',
                'alamat' => 'Jl. Sudirman No. 2, Jakarta',
                'jenis_kelamin' => 'Perempuan',
                'tanggal_lahir' => '1998-08-20',
                'no_hp' => '081234567891',
                'status' => 'aktif',
            ],
            [
                'nama' => 'Adi Wijaya',
                'id_jabatan' => $jabatans[1]->id, // Junior Dev
                'nik' => '12345678901234503',
                'alamat' => 'Jl. Gatot Subroto No. 3, Jakarta',
                'jenis_kelamin' => 'Laki-laki',
                'tanggal_lahir' => '1999-03-10',
                'no_hp' => '081234567892',
                'status' => 'aktif',
            ],
            [
                'nama' => 'Dewi Lestari',
                'id_jabatan' => $jabatans[2]->id, // Designer
                'nik' => '12345678901234504',
                'alamat' => 'Jl. Rasuna Said No. 4, Jakarta',
                'jenis_kelamin' => 'Perempuan',
                'tanggal_lahir' => '1995-12-25',
                'no_hp' => '081234567893',
                'status' => 'aktif',
            ],
            [
                'nama' => 'Raka Setiawan',
                'id_jabatan' => $jabatans[3]->id, // PM
                'nik' => '12345678901234505',
                'alamat' => 'Jl. Kuningan No. 5, Jakarta',
                'jenis_kelamin' => 'Laki-laki',
                'tanggal_lahir' => '1992-07-08',
                'no_hp' => '081234567894',
                'status' => 'aktif',
            ],
            [
                'nama' => 'Maya Putri',
                'id_jabatan' => $jabatans[4]->id, // QA
                'nik' => '12345678901234506',
                'alamat' => 'Jl. Blok M No. 6, Jakarta',
                'jenis_kelamin' => 'Perempuan',
                'tanggal_lahir' => '1996-11-30',
                'no_hp' => '081234567895',
                'status' => 'aktif',
            ],
        ];

        $created = [];
        foreach ($karyawans as $karyawan) {
            $created[] = Karyawan::create($karyawan);
            $this->command->line("  ✓ Created: {$karyawan['nama']}");
        }

        return $created;
    }

    /**
     * Create Clients
     */
    private function createClients(): array
    {
        $clients = [
            [
                'nama_client' => 'PT Digital Indonesia',
                'alamat' => 'Jl. Merdeka No. 100, Jakarta',
                'no_hp' => '021-1234567',
                'email' => 'contact@digital-indonesia.com',
                'deskripsi' => 'Digital transformation service provider',
            ],
            [
                'nama_client' => 'CV Teknologi Maju',
                'alamat' => 'Jl. Sudirman No. 200, Jakarta',
                'no_hp' => '021-7654321',
                'email' => 'info@teknologi-maju.com',
                'deskripsi' => 'Technology consulting company',
            ],
            [
                'nama_client' => 'PT Startup Inovatif',
                'alamat' => 'Jl. Gatot Subroto No. 300, Jakarta',
                'no_hp' => '021-5555555',
                'email' => 'hello@startup-inovatif.com',
                'deskripsi' => 'Fast-growing startup company',
            ],
        ];

        $created = [];
        foreach ($clients as $client) {
            $created[] = Client::create($client);
            $this->command->line("  ✓ Created: {$client['nama_client']}");
        }

        return $created;
    }

    /**
     * Create Kontraks (Contracts) with attached Karyawans
     */
    private function createKontraks(array $clients, array $karyawans): array
    {
        $now = Carbon::now();
        $startMonth = $now->copy()->subMonths(1)->startOfMonth();
        $endMonth = $now->copy()->addMonths(2)->endOfMonth();

        $kontraks = [
            [
                'client_id' => $clients[0]->id,
                'judul' => 'Website Revamp Project',
                'deskripsi' => 'Complete website redesign and modernization',
                'tanggal_mulai' => $startMonth->format('Y-m-d'),
                'tanggal_selesai' => $startMonth->copy()->addDays(60)->format('Y-m-d'),
                'total_biaya' => 150000000,
                'tanggal_gajian' => 5,
                'status' => 'aktif',
                'karyawans' => [$karyawans[0], $karyawans[1], $karyawans[3]], // Senior Dev, Junior Dev, Designer
            ],
            [
                'client_id' => $clients[1]->id,
                'judul' => 'Mobile App Development',
                'deskripsi' => 'Native mobile app for iOS and Android',
                'tanggal_mulai' => $startMonth->copy()->addDays(15)->format('Y-m-d'),
                'tanggal_selesai' => $startMonth->copy()->addDays(90)->format('Y-m-d'),
                'total_biaya' => 200000000,
                'tanggal_gajian' => 10,
                'status' => 'aktif',
                'karyawans' => [$karyawans[0], $karyawans[1], $karyawans[2], $karyawans[4]], // Senior Dev, 2x Junior Dev, QA
            ],
            [
                'client_id' => $clients[2]->id,
                'judul' => 'ERP System Implementation',
                'deskripsi' => 'Enterprise resource planning system setup',
                'tanggal_mulai' => $startMonth->copy()->subMonths(2)->format('Y-m-d'),
                'tanggal_selesai' => $startMonth->copy()->addMonths(1)->format('Y-m-d'),
                'total_biaya' => 300000000,
                'tanggal_gajian' => 20,
                'status' => 'selesai',
                'karyawans' => [$karyawans[0], $karyawans[4], $karyawans[5]], // Senior Dev, QA, PM
            ],
            [
                'client_id' => $clients[0]->id,
                'judul' => 'UI/UX Design Workshop',
                'deskripsi' => 'Design thinking and UI/UX best practices',
                'tanggal_mulai' => $now->format('Y-m-d'),
                'tanggal_selesai' => $now->copy()->addDays(30)->format('Y-m-d'),
                'total_biaya' => 50000000,
                'tanggal_gajian' => 15,
                'status' => 'aktif',
                'karyawans' => [$karyawans[3]], // Designer
            ],
        ];

        $created = [];
        foreach ($kontraks as $data) {
            $karyawansList = $data['karyawans'];
            unset($data['karyawans']);

            $kontrak = Kontrak::create($data);

            // Attach karyawans to kontrak
            foreach ($karyawansList as $karyawan) {
                KontrakKaryawan::create([
                    'kontrak_id' => $kontrak->id,
                    'karyawan_id' => $karyawan->id,
                ]);
            }

            $created[] = $kontrak;
            $karyawanCount = count($karyawansList);
            $this->command->line("  ✓ Created: {$data['judul']} ({$data['status']}) - {$karyawanCount} karyawan");
        }

        return $created;
    }

    /**
     * Generate Penggajian (Payroll) for Kontraks
     */
    private function generatePenggajian(array $kontraks): void
    {
        $service = new PenggajianService();

        foreach ($kontraks as $kontrak) {
            try {
                // Load kontrak dengan relations
                $kontrak = Kontrak::with('kontrakKaryawans.karyawan.jabatan', 'kontrakKaryawans.karyawan.cashbons')
                    ->find($kontrak->id);

                $result = $service->generate($kontrak);

                if ($result['success']) {
                    $this->command->line("  ✓ {$kontrak->judul}: {$result['created_count']} penggajian generated");
                } else {
                    $this->command->line("  ✗ {$kontrak->judul}: {$result['message']}");
                }
            } catch (\Exception $e) {
                $this->command->line("  ✗ {$kontrak->judul}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Create Cashbons for Karyawans
     */
    private function createCashbons(array $karyawans): void
    {
        $cashbons = [
            [
                'karyawan' => $karyawans[0], // Budi
                'jumlah' => 3000000,
                'keterangan' => 'Keperluan darurat',
                'status' => 'dibayar', // Already paid
            ],
            [
                'karyawan' => $karyawans[1], // Siti
                'jumlah' => 2000000,
                'keterangan' => 'Pinjaman pendidikan',
                'status' => 'belum_dibayar', // Pending - will be deducted from payroll
            ],
            [
                'karyawan' => $karyawans[2], // Adi
                'jumlah' => 5000000,
                'keterangan' => 'Cicilan rumah',
                'status' => 'belum_dibayar', // Pending
            ],
            [
                'karyawan' => $karyawans[3], // Dewi
                'jumlah' => 1500000,
                'keterangan' => 'Biaya kesehatan',
                'status' => 'dibayar',
            ],
            [
                'karyawan' => $karyawans[0], // Budi - second cashbon
                'jumlah' => 2500000,
                'keterangan' => 'Kebutuhan keluarga',
                'status' => 'belum_dibayar', // Pending
            ],
        ];

        foreach ($cashbons as $data) {
            $karyawan = $data['karyawan'];
            $karyawan->cashbons()->create([
                'jumlah' => $data['jumlah'],
                'keterangan' => $data['keterangan'],
                'status' => $data['status'],
            ]);

            $this->command->line("  ✓ Cashbon Rp {$data['jumlah']} untuk {$karyawan->nama} ({$data['status']})");
        }
    }

    /**
     * Mark some Penggajians as paid
     */
    private function markPenggajianPaid(): void
    {
        // Mark last penggajian of completed contracts as paid
        $selesaiKontraks = Kontrak::where('status', 'selesai')->get();

        foreach ($selesaiKontraks as $kontrak) {
            $penggajians = Penggajian::where('kontrak_id', $kontrak->id)
                ->orderBy('periode', 'desc')
                ->limit(1)
                ->get();

            foreach ($penggajians as $penggajian) {
                $penggajian->update(['status' => 'dibayar']);
                $this->command->line("  ✓ Updated: {$kontrak->judul} - Penggajian dibayar");
            }
        }

        // Mark first penggajian of first active contract as paid
        $firstActiveKontrak = Kontrak::where('status', 'aktif')->first();
        if ($firstActiveKontrak) {
            $firstPenggajian = Penggajian::where('kontrak_id', $firstActiveKontrak->id)
                ->orderBy('periode', 'asc')
                ->first();

            if ($firstPenggajian) {
                $firstPenggajian->update(['status' => 'dibayar']);
                $this->command->line("  ✓ Updated: {$firstActiveKontrak->judul} - Penggajian pertama dibayar");
            }
        }
    }

    /**
     * Print summary of seeded data
     */
    private function printSummary(): void
    {
        $summary = [
            'Clients' => Client::count(),
            'Jabatan' => Jabatan::count(),
            'Karyawan' => Karyawan::count(),
            'Kontrak' => Kontrak::count(),
            'KontrakKaryawan' => KontrakKaryawan::count(),
            'Penggajian' => Penggajian::count(),
            'Penggajian Details' => PenggajianDetail::count(),
            'Cashbon' => Cashbon::count(),
        ];

        $this->command->info('📊 Seeding Summary:');
        $this->command->info('─────────────────────────────────────');
        foreach ($summary as $label => $count) {
            $this->command->line(sprintf('  %s: <fg=cyan>%d</>', $label, $count));
        }
        $this->command->info('─────────────────────────────────────');

        // Financial Summary
        $totalBiaya = Kontrak::sum('total_biaya');
        $totalPenggajian = PenggajianDetail::sum('total_gaji');
        $totalKeuntungan = $totalBiaya - $totalPenggajian;

        $this->command->info('💰 Financial Summary:');
        $this->command->info('─────────────────────────────────────');
        $this->command->line(sprintf('  Total Biaya Kontrak: Rp %s', number_format($totalBiaya)));
        $this->command->line(sprintf('  Total Penggajian: Rp %s', number_format($totalPenggajian)));
        $this->command->line(sprintf('  Keuntungan Bersih: Rp %s', number_format($totalKeuntungan)));
        $this->command->info('─────────────────────────────────────');
    }
}
