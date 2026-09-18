<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Buku besar alokasi potongan cashbon.
 *
 * Setiap baris menjawab satu pertanyaan yang selama ini tidak bisa dijawab
 * sistem: cashbon mana menyumbang berapa rupiah pada slip gaji mana. Tanpa
 * catatan ini, melepas sebuah potongan hanya bisa ditebak — dan tebakan itulah
 * yang dahulu mengurangi nominal dari setiap slip pekerja sekaligus.
 *
 * `cashbon_id` sengaja restrictOnDelete: buku besar yang bisa terhapus diam-diam
 * bukan buku besar. Pelepasan alokasi harus melalui service, yang menolak
 * menyentuh penggajian yang sudah dibayar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashbon_potongans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cashbon_id')->constrained()->restrictOnDelete();
            $table->foreignId('penggajian_detail_id')->constrained()->cascadeOnDelete();
            $table->decimal('jumlah', 15, 2);
            $table->timestamps();

            // Satu cashbon menyumbang paling banyak satu baris per detail
            $table->unique(['cashbon_id', 'penggajian_detail_id']);

            // Menopang query pelepasan alokasi, yang menyaring berdasarkan
            // detail dan tidak bisa memakai unique di atas karena berawalan
            // kolom cashbon_id
            $table->index('penggajian_detail_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashbon_potongans');
    }
};
