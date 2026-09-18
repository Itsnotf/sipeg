<?php

namespace Database\Seeders;

use App\Enums\StatusKaryawan;
use App\Enums\StatusKontrak;
use App\Models\Cashbon;
use App\Models\Client;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\Kontrak;
use App\Models\KontrakKaryawan;
use App\Models\Penggajian;
use App\Models\PenggajianDetail;
use App\Services\CashbonService;
use App\Services\PenggajianService;
use Illuminate\Database\Seeder;

/**
 * Data contoh yang melewati jalur yang sama dengan aplikasi sebenarnya.
 *
 * Seeder lama menulis status secara harfiah dan memanggil Eloquent langsung,
 * sehingga menghasilkan data yang konsisten dengan dirinya sendiri tetapi tidak
 * dengan apa yang dihasilkan antarmuka — dan itulah yang dahulu menyembunyikan
 * bug potongan cashbon dari seluruh pengujian.
 */
class AppDataSeeder extends Seeder
{
    public function run(): void
    {
        $jabatans = $this->buatJabatan();
        $karyawans = $this->buatKaryawan($jabatans);
        $clients = $this->buatClient();
        $kontraks = $this->buatKontrak($clients, $karyawans);

        $this->prosesPenggajian($kontraks);
        $this->buatCashbon($karyawans);
        $this->bayarSebagianPenggajian($kontraks);

        $this->cetakRingkasan();
    }

    /**
     * @return array<string, Jabatan>
     */
    private function buatJabatan(): array
    {
        $daftar = [
            'senior' => ['Senior Developer', 15_000_000, 5.0],
            'junior' => ['Junior Developer', 8_000_000, 5.0],
            'desainer' => ['UI/UX Designer', 10_000_000, 5.0],
            'manajer' => ['Project Manager', 12_000_000, 4.0],
            'qa' => ['QA Engineer', 9_000_000, 5.0],
        ];

        $hasil = [];

        foreach ($daftar as $kunci => [$nama, $gaji, $persen]) {
            $hasil[$kunci] = Jabatan::create([
                'nama_jabatan' => $nama,
                'deskripsi' => "Posisi {$nama}",
                'gaji' => $gaji,
                'bpjs' => $gaji * $persen / 100,
                'bpjs_persen' => $persen,
            ]);
        }

        return $hasil;
    }

    /**
     * @param  array<string, Jabatan>  $jabatans
     * @return array<int, Karyawan>
     */
    private function buatKaryawan(array $jabatans): array
    {
        $daftar = [
            ['Budi Hartono', 'senior', 'L'],
            ['Siti Nurhaliza', 'desainer', 'P'],
            ['Adi Wijaya', 'junior', 'L'],
            ['Dewi Lestari', 'manajer', 'P'],
            ['Raka Setiawan', 'qa', 'L'],
            ['Maya Putri', 'junior', 'P'],
        ];

        $hasil = [];

        foreach ($daftar as $i => [$nama, $jabatan, $kelamin]) {
            $hasil[] = Karyawan::create([
                'id_jabatan' => $jabatans[$jabatan]->id,
                'nama' => $nama,
                'nik' => '32730'.str_pad((string) ($i + 1), 11, '0', STR_PAD_LEFT),
                'alamat' => 'Jl. Contoh No. '.($i + 1).', Bandung',
                'jenis_kelamin' => $kelamin,
                'tanggal_lahir' => now()->subYears(28 + $i)->format('Y-m-d'),
                'no_hp' => '0812'.str_pad((string) (10000000 + $i), 8, '0', STR_PAD_LEFT),
                'status' => StatusKaryawan::NonAktif,
            ]);
        }

        return $hasil;
    }

    /**
     * @return array<int, Client>
     */
    private function buatClient(): array
    {
        return [
            Client::create([
                'nama_client' => 'PT Digital Indonesia',
                'alamat' => 'Jl. Sudirman No. 45, Jakarta',
                'email' => 'kontak@digitalindonesia.co.id',
                'no_hp' => '02198765432',
                'deskripsi' => 'Perusahaan teknologi finansial',
            ]),
            Client::create([
                'nama_client' => 'CV Teknologi Maju',
                'alamat' => 'Jl. Asia Afrika No. 12, Bandung',
                'email' => 'admin@teknologimaju.co.id',
                'no_hp' => '02287654321',
                'deskripsi' => 'Penyedia perangkat lunak ritel',
            ]),
            Client::create([
                'nama_client' => 'PT Startup Inovatif',
                'alamat' => 'Jl. Gatot Subroto No. 88, Jakarta',
                'email' => 'halo@startupinovatif.id',
                'no_hp' => '02176543210',
                'deskripsi' => 'Rintisan bidang logistik',
            ]),
        ];
    }

    /**
     * Setiap pekerja hanya ditempatkan pada satu kontrak. Penempatan ganda
     * membuat plafon potongan yang sama diterapkan dua kali pada hutang yang
     * sama, dan hasilnya bergantung pada urutan pemrosesan.
     *
     * @param  array<int, Client>  $clients
     * @param  array<int, Karyawan>  $karyawans
     * @return array<int, Kontrak>
     */
    private function buatKontrak(array $clients, array $karyawans): array
    {
        $rencana = [
            [
                'client' => 0,
                'judul' => 'Pengembangan Aplikasi Mobile',
                'mulai' => now()->subMonths(5)->startOfMonth(),
                'selesai' => now()->addMonths(4)->endOfMonth(),
                'gajian' => 25,
                'biaya' => 450_000_000,
                'status' => StatusKontrak::Progres,
                'pekerja' => [0, 1],
            ],
            [
                'client' => 1,
                'judul' => 'Pemeliharaan Sistem Ritel',
                'mulai' => now()->subMonths(3)->startOfMonth(),
                'selesai' => now()->addMonths(6)->endOfMonth(),
                'gajian' => 10,
                'biaya' => 320_000_000,
                'status' => StatusKontrak::Progres,
                'pekerja' => [2, 3],
            ],
            [
                'client' => 2,
                'judul' => 'Audit Kualitas Perangkat Lunak',
                'mulai' => now()->subMonths(6)->startOfMonth(),
                'selesai' => now()->subMonth()->endOfMonth(),
                'gajian' => 5,
                'biaya' => 180_000_000,
                'status' => StatusKontrak::Selesai,
                'pekerja' => [4],
            ],
            [
                'client' => 0,
                'judul' => 'Perancangan Ulang Antarmuka',
                'mulai' => now()->addMonth()->startOfMonth(),
                'selesai' => now()->addMonths(7)->endOfMonth(),
                'gajian' => 20,
                'biaya' => 260_000_000,
                'status' => StatusKontrak::Pending,
                'pekerja' => [5],
            ],
        ];

        $hasil = [];

        foreach ($rencana as $item) {
            $kontrak = Kontrak::create([
                'client_id' => $clients[$item['client']]->id,
                'judul' => $item['judul'],
                'deskripsi' => 'Kontrak penyediaan tenaga kerja untuk '.$item['judul'],
                'tanggal_mulai' => $item['mulai']->format('Y-m-d'),
                'tanggal_selesai' => $item['selesai']->format('Y-m-d'),
                'tanggal_gajian' => $item['gajian'],
                'total_biaya' => $item['biaya'],
                'status' => $item['status'],
            ]);

            foreach ($item['pekerja'] as $indeks) {
                KontrakKaryawan::create([
                    'kontrak_id' => $kontrak->id,
                    'karyawan_id' => $karyawans[$indeks]->id,
                    'tanggal_mulai' => $item['mulai']->format('Y-m-d'),
                ]);

                $karyawans[$indeks]->update([
                    'status' => $item['status'] === StatusKontrak::Selesai
                        ? StatusKaryawan::NonAktif
                        : StatusKaryawan::Aktif,
                ]);
            }

            $hasil[] = $kontrak;
        }

        return $hasil;
    }

    /**
     * @param  array<int, Kontrak>  $kontraks
     */
    private function prosesPenggajian(array $kontraks): void
    {
        $service = app(PenggajianService::class);

        foreach ($kontraks as $kontrak) {
            // Kontrak yang belum berjalan sengaja dilewati: penggajiannya
            // memang belum boleh ada, dan sejak ada penjagaannya di service
            // memanggilnya di sini akan melempar exception.
            if (! $kontrak->status->bolehDiproses()) {
                continue;
            }

            $service->proses($kontrak);
        }
    }

    /**
     * Cashbon dibuat lewat service yang sama dengan yang dipakai controller,
     * sehingga potongannya benar-benar tercatat di buku besar.
     *
     * @param  array<int, Karyawan>  $karyawans
     */
    private function buatCashbon(array $karyawans): void
    {
        $service = app(CashbonService::class);

        $daftar = [
            [0, 6_000_000, 'Pinjaman biaya pendidikan anak'],
            [1, 2_500_000, 'Pinjaman perbaikan kendaraan'],
            [2, 1_500_000, 'Pinjaman keperluan keluarga'],
            [3, 9_000_000, 'Pinjaman renovasi rumah'],
        ];

        foreach ($daftar as [$indeks, $jumlah, $keterangan]) {
            try {
                $service->buat([
                    'karyawan_id' => $karyawans[$indeks]->id,
                    'jumlah' => $jumlah,
                    'keterangan' => $keterangan,
                ]);
            } catch (\Throwable $e) {
                $this->command?->warn("  Cashbon dilewati ({$keterangan}): {$e->getMessage()}");
            }
        }
    }

    /**
     * Membayar penggajian tertua setiap kontrak lewat service, agar alokasi
     * cashbon ikut diselesaikan dan statusnya tersegarkan.
     *
     * @param  array<int, Kontrak>  $kontraks
     */
    private function bayarSebagianPenggajian(array $kontraks): void
    {
        $service = app(PenggajianService::class);

        foreach ($kontraks as $kontrak) {
            $penggajian = Penggajian::where('kontrak_id', $kontrak->id)
                ->orderBy('periode')
                ->first();

            if ($penggajian && ! $penggajian->terkunci()) {
                $service->tandaiDibayar($penggajian);
            }
        }
    }

    private function cetakRingkasan(): void
    {
        $biaya = (float) Kontrak::sum('total_biaya');
        $gaji = (float) PenggajianDetail::sum('total_gaji');

        $this->command?->newLine();
        $this->command?->info('Ringkasan data contoh');
        $this->command?->table(
            ['Entitas', 'Jumlah'],
            [
                ['Client', Client::count()],
                ['Jabatan', Jabatan::count()],
                ['Karyawan', Karyawan::count()],
                ['Kontrak', Kontrak::count()],
                ['Penempatan', KontrakKaryawan::count()],
                ['Penggajian', Penggajian::count()],
                ['Detail penggajian', PenggajianDetail::count()],
                ['Cashbon', Cashbon::count()],
            ]
        );

        $this->command?->line(sprintf(
            '  Total biaya kontrak  : Rp %s', number_format($biaya, 0, ',', '.')
        ));
        $this->command?->line(sprintf(
            '  Total gaji tersusun  : Rp %s', number_format($gaji, 0, ',', '.')
        ));
        $this->command?->line(sprintf(
            '  Selisih              : Rp %s', number_format($biaya - $gaji, 0, ',', '.')
        ));
    }
}
