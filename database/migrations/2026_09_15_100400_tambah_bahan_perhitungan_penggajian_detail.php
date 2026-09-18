<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menyimpan bahan perhitungan, bukan hanya hasilnya.
 *
 * Membekukan hasil membuat nominal tidak bisa dihitung ulang ketika roster
 * atau tanggal penempatan berubah. Membekukan bahan — gaji penuh jabatan dan
 * persentase BPJS pada saat periode dibuat — membuat perhitungan ulang
 * menghasilkan angka yang sama persis, sekaligus membuat setiap baris slip
 * bisa ditelusuri asalnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        $default = (float) config('payroll.bpjs_persen_default', 5.0);

        Schema::table('penggajian_details', function (Blueprint $table) use ($default) {
            $table->decimal('gaji_pokok_penuh', 15, 2)->default(0)->after('karyawan_id');
            $table->decimal('bpjs_persen', 5, 2)->default($default)->after('gaji_pokok');
            $table->unsignedSmallInteger('hari_aktif')->default(0)->after('bpjs');
            $table->unsignedSmallInteger('hari_periode')->default(30)->after('hari_aktif');
        });

        $this->isiBahanBarisLama($default);
    }

    public function down(): void
    {
        Schema::table('penggajian_details', function (Blueprint $table) {
            $table->dropColumn(['gaji_pokok_penuh', 'bpjs_persen', 'hari_aktif', 'hari_periode']);
        });
    }

    /**
     * Baris lama tidak mengenal prorata, sehingga gaji tercatat sama dengan
     * gaji penuh dan hari aktif sama dengan panjang bulannya.
     */
    private function isiBahanBarisLama(float $default): void
    {
        $details = DB::table('penggajian_details')
            ->join('penggajians', 'penggajian_details.penggajian_id', '=', 'penggajians.id')
            ->select(
                'penggajian_details.id',
                'penggajian_details.gaji_pokok',
                'penggajian_details.bpjs',
                'penggajians.periode'
            )
            ->get();

        foreach ($details as $detail) {
            $gaji = (float) $detail->gaji_pokok;
            $hari = CarbonImmutable::parse($detail->periode)->daysInMonth;

            DB::table('penggajian_details')->where('id', $detail->id)->update([
                'gaji_pokok_penuh' => $gaji,
                'bpjs_persen' => $gaji > 0
                    ? min(99.99, round((float) $detail->bpjs / $gaji * 100, 2))
                    : $default,
                'hari_aktif' => $hari,
                'hari_periode' => $hari,
            ]);
        }
    }
};
