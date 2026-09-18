<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tanggal masuk dan keluar seorang pekerja dari sebuah kontrak. Tanpa ini gaji
 * tidak mungkin dihitung proporsional untuk pekerja yang bergabung atau
 * berhenti di tengah bulan.
 *
 * `tanggal_selesai` kosong berarti penempatan masih berjalan. Pemberhentian
 * mengisi kolom ini, bukan menghapus barisnya — baris yang dihapus akan
 * menghilangkan hari yang sudah benar-benar dikerjakan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kontrak_karyawans', function (Blueprint $table) {
            $table->date('tanggal_mulai')->nullable()->after('karyawan_id');
            $table->date('tanggal_selesai')->nullable()->after('tanggal_mulai');
        });

        // Penempatan lama dianggap berlaku sejak kontraknya dimulai.
        // Ditulis sebagai loop, bukan UPDATE ... JOIN, agar ikut berjalan di
        // SQLite yang dipakai suite pengujian.
        $kontraks = DB::table('kontraks')->select('id', 'tanggal_mulai')->get();

        foreach ($kontraks as $kontrak) {
            DB::table('kontrak_karyawans')
                ->where('kontrak_id', $kontrak->id)
                ->whereNull('tanggal_mulai')
                ->update(['tanggal_mulai' => $kontrak->tanggal_mulai]);
        }
    }

    public function down(): void
    {
        Schema::table('kontrak_karyawans', function (Blueprint $table) {
            $table->dropColumn(['tanggal_mulai', 'tanggal_selesai']);
        });
    }
};
