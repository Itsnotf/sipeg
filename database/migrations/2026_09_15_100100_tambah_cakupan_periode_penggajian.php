<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menyimpan cakupan kerja yang dibayar oleh sebuah periode, terpisah dari
 * tanggal pembayarannya. Selama ini penggajian hanya punya satu tanggal tanpa
 * keterangan rentang kerja mana yang sebenarnya dibayar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penggajians', function (Blueprint $table) {
            $table->date('periode_mulai')->nullable()->after('periode');
            $table->date('periode_selesai')->nullable()->after('periode_mulai');
            $table->date('tanggal_bayar')->nullable()->after('periode_selesai');
            $table->boolean('final')->default(false)->after('tanggal_bayar');
        });

        $this->isiCakupanBarisLama();
    }

    public function down(): void
    {
        Schema::table('penggajians', function (Blueprint $table) {
            $table->dropColumn(['periode_mulai', 'periode_selesai', 'tanggal_bayar', 'final']);
        });
    }

    /**
     * Menghitung cakupan baris yang sudah ada langsung di sini, bukan lewat
     * JadwalGajian — migrasi tidak boleh bergantung pada kode aplikasi yang
     * masih bisa berubah setelahnya.
     */
    private function isiCakupanBarisLama(): void
    {
        $penggajians = DB::table('penggajians')
            ->join('kontraks', 'penggajians.kontrak_id', '=', 'kontraks.id')
            ->select(
                'penggajians.id',
                'penggajians.periode',
                'kontraks.tanggal_mulai',
                'kontraks.tanggal_selesai',
                'kontraks.tanggal_gajian'
            )
            ->get();

        foreach ($penggajians as $baris) {
            $awalBulan = CarbonImmutable::parse($baris->periode)->startOfMonth();
            $akhirBulan = $awalBulan->endOfMonth()->startOfDay();

            $kontrakMulai = CarbonImmutable::parse($baris->tanggal_mulai)->startOfDay();
            $kontrakSelesai = CarbonImmutable::parse($baris->tanggal_selesai)->startOfDay();

            $selesai = $akhirBulan->min($kontrakSelesai);
            $bulanBayar = $awalBulan->addMonth();
            $hariGajian = max(1, min((int) $baris->tanggal_gajian, $bulanBayar->daysInMonth));

            DB::table('penggajians')->where('id', $baris->id)->update([
                'periode_mulai' => $awalBulan->max($kontrakMulai)->format('Y-m-d'),
                'periode_selesai' => $selesai->format('Y-m-d'),
                'tanggal_bayar' => $bulanBayar->day($hariGajian)->format('Y-m-d'),
                'final' => $selesai->equalTo($kontrakSelesai),
            ]);
        }
    }
};
