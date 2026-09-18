<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Validasi menyatakan deskripsi client boleh kosong, kolomnya tidak.
|
| Formulir client memberi tanda "opsional" pada bidang ini dan StoreRequest
| menandainya nullable, tetapi kolomnya NOT NULL — menyimpan client tanpa
| deskripsi berujung galat SQL, bukan pesan validasi.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->string('deskripsi')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->string('deskripsi')->nullable(false)->change();
        });
    }
};
