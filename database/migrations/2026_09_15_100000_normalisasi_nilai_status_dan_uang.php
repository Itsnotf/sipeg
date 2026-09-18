<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menyeragamkan nilai yang selama ini ditulis dengan ejaan berbeda-beda,
 * sebelum kolomnya dijaga oleh enum dan tipe desimal.
 *
 * Migrasi ini sengaja tidak menyentuh skema sama sekali — hanya isinya —
 * sehingga tidak mungkin gagal di tengah dan meninggalkan tabel setengah jadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Cashbon: 'belum dibayar' (spasi) dan 'belum_dibayar' (garis bawah)
        // adalah dua ejaan untuk keadaan yang sama. Inilah akar bug D1.
        DB::table('cashbons')->whereIn('status', ['belum dibayar', 'belum_dibayar'])
            ->update(['status' => 'berjalan']);
        DB::table('cashbons')->whereIn('status', ['dibayar', 'sudah_dibayar', 'lunas'])
            ->update(['status' => 'lunas']);
        DB::table('cashbons')->whereNotIn('status', ['berjalan', 'lunas'])
            ->update(['status' => 'berjalan']);

        // Kontrak: UI menulis Pending/Progres/Selesai, seeder menulis aktif/selesai
        DB::table('kontraks')->whereIn('status', ['aktif', 'Aktif', 'progres', 'Progres'])
            ->update(['status' => 'Progres']);
        DB::table('kontraks')->whereIn('status', ['selesai', 'Selesai'])
            ->update(['status' => 'Selesai']);
        DB::table('kontraks')->whereNotIn('status', ['Progres', 'Selesai'])
            ->update(['status' => 'Pending']);

        // Karyawan: controller menulis 'Aktif'/'Non Aktif', seeder menulis 'aktif'
        DB::table('karyawans')->whereIn('status', ['aktif', 'Aktif'])
            ->update(['status' => 'Aktif']);
        DB::table('karyawans')->where('status', '!=', 'Aktif')
            ->update(['status' => 'Non Aktif']);

        DB::table('karyawans')->whereIn('jenis_kelamin', ['L', 'l', 'Laki-laki', 'laki-laki'])
            ->update(['jenis_kelamin' => 'L']);
        DB::table('karyawans')->where('jenis_kelamin', '!=', 'L')
            ->update(['jenis_kelamin' => 'P']);

        DB::table('penggajians')->whereNotIn('status', ['belum_dibayar', 'dibayar'])
            ->update(['status' => 'belum_dibayar']);

        // Periode berubah makna: dahulu tanggal gajian, kini identitas bulan
        // yang dibayar. Algoritma lama hanya menghasilkan satu tanggal gajian
        // per bulan, sehingga pemetaan ini satu lawan satu.
        foreach (DB::table('penggajians')->select('id', 'periode')->get() as $baris) {
            DB::table('penggajians')->where('id', $baris->id)->update([
                'periode' => substr((string) $baris->periode, 0, 7).'-01',
            ]);
        }

        foreach ([['jabatans', 'gaji'], ['jabatans', 'bpjs'], ['kontraks', 'total_biaya'], ['cashbons', 'jumlah']] as [$tabel, $kolom]) {
            $this->bersihkanNominal($tabel, $kolom);
        }
    }

    public function down(): void
    {
        DB::table('cashbons')->where('status', 'berjalan')->update(['status' => 'belum_dibayar']);
        DB::table('cashbons')->where('status', 'lunas')->update(['status' => 'dibayar']);
    }

    /**
     * Membuang karakter selain angka dan satu titik desimal. Nilai yang tidak
     * bisa diselamatkan menjadi 0 agar perubahan tipe ke desimal tidak ditolak
     * MySQL dalam mode ketat.
     */
    private function bersihkanNominal(string $tabel, string $kolom): void
    {
        foreach (DB::table($tabel)->select('id', $kolom)->get() as $baris) {
            $asli = (string) $baris->{$kolom};
            $bersih = preg_replace('/[^0-9.]/', '', $asli) ?? '';

            $bagian = explode('.', $bersih);
            $hasil = array_shift($bagian);
            $hasil = ($hasil === '' || $hasil === null) ? '0' : $hasil;

            if ($bagian !== []) {
                $hasil .= '.'.substr(implode('', $bagian), 0, 2);
            }

            if (! is_numeric($hasil)) {
                $hasil = '0';
            }

            if ($hasil !== $asli) {
                DB::table($tabel)->where('id', $baris->id)->update([$kolom => $hasil]);
            }
        }
    }
};
