<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Satu pekerja hanya boleh punya satu baris dalam satu penggajian.
 *
 * Observer lama membuat baris detail dengan firstOrCreate tanpa batasan apa pun
 * di belakangnya, sehingga baris kembar mungkin terbentuk dan menggandakan
 * total gaji sebuah periode. Kini buku besar alokasi bergantung pada keunikan
 * ini, jadi batasannya ditegakkan di basis data.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->hapusBarisKembar();

        Schema::table('penggajian_details', function (Blueprint $table) {
            $table->unique(['penggajian_id', 'karyawan_id']);
        });
    }

    public function down(): void
    {
        Schema::table('penggajian_details', function (Blueprint $table) {
            $table->dropUnique(['penggajian_id', 'karyawan_id']);
        });
    }

    /**
     * Menyisakan baris tertua untuk setiap pasangan penggajian dan karyawan.
     */
    private function hapusBarisKembar(): void
    {
        $kembar = DB::table('penggajian_details')
            ->select('penggajian_id', 'karyawan_id', DB::raw('MIN(id) as id_disimpan'))
            ->groupBy('penggajian_id', 'karyawan_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($kembar as $baris) {
            DB::table('penggajian_details')
                ->where('penggajian_id', $baris->penggajian_id)
                ->where('karyawan_id', $baris->karyawan_id)
                ->where('id', '!=', $baris->id_disimpan)
                ->delete();
        }
    }
};
