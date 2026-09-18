<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nominal uang berpindah dari text/string ke decimal.
 *
 * Penyimpanan sebagai teks memaksa controller membersihkan digit secara manual,
 * dan pembersih itu membuang titik desimal sehingga "1234.50" menjadi 123450 —
 * memperbesar nilai seratus kali lipat. Tipe decimal menghapus seluruh kelas
 * kesalahan tersebut sekaligus memungkinkan penjumlahan di sisi basis data.
 *
 * Nilai sudah dibersihkan lebih dahulu oleh migrasi normalisasi, sehingga MySQL
 * dalam mode ketat tidak menolak perubahan tipe ini.
 *
 * Catatan: sejak Laravel 11, change() membuang setiap modifier yang tidak
 * disebutkan ulang. Karena itu nullable dan default ditulis eksplisit di sini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jabatans', function (Blueprint $table) {
            $table->decimal('gaji', 15, 2)->default(0)->change();
            $table->decimal('bpjs', 15, 2)->nullable()->change();
        });

        Schema::table('kontraks', function (Blueprint $table) {
            $table->decimal('total_biaya', 15, 2)->default(0)->change();
        });

        Schema::table('cashbons', function (Blueprint $table) {
            $table->decimal('jumlah', 15, 2)->default(0)->change();
            $table->string('status')->default('berjalan')->change();
        });
    }

    public function down(): void
    {
        Schema::table('jabatans', function (Blueprint $table) {
            $table->text('gaji')->change();
            $table->text('bpjs')->change();
        });

        Schema::table('kontraks', function (Blueprint $table) {
            $table->string('total_biaya')->change();
        });

        Schema::table('cashbons', function (Blueprint $table) {
            $table->string('jumlah')->change();
            $table->string('status')->default('belum_dibayar')->change();
        });
    }
};
