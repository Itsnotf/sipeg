<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('penggajian_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penggajian_id')->constrained()->cascadeOnDelete();
            $table->foreignId('karyawan_id')->constrained()->cascadeOnDelete();
            $table->decimal('gaji_pokok', 15, 2);
            $table->decimal('bpjs', 15, 2)->default(0);
            $table->decimal('potongan_cashbon', 15, 2)->default(0);
            $table->decimal('total_gaji', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penggajian_details');
    }
};
