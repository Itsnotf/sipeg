<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BPJS berubah dari nominal rupiah tetap menjadi persentase gaji pokok,
 * sehingga ikut menyesuaikan saat gaji berubah maupun saat gaji diprorata.
 *
 * Kolom `bpjs` lama sengaja dipertahankan — konversi ke persen bersifat lossy
 * dan angka historisnya masih berguna sebagai rujukan.
 */
return new class extends Migration
{
    public function up(): void
    {
        $default = (float) config('payroll.bpjs_persen_default', 5.0);

        Schema::table('jabatans', function (Blueprint $table) use ($default) {
            $table->decimal('bpjs_persen', 5, 2)->default($default)->after('bpjs');
        });

        foreach (DB::table('jabatans')->select('id', 'gaji', 'bpjs')->get() as $jabatan) {
            $gaji = (float) $jabatan->gaji;
            $bpjs = (float) $jabatan->bpjs;

            DB::table('jabatans')->where('id', $jabatan->id)->update([
                'bpjs_persen' => $gaji > 0
                    ? min(99.99, round($bpjs / $gaji * 100, 2))
                    : $default,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('jabatans', function (Blueprint $table) {
            $table->dropColumn('bpjs_persen');
        });
    }
};
