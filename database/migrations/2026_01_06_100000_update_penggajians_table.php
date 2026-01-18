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
        Schema::table('penggajians', function (Blueprint $table) {
            // Add unique constraint on kontrak_id and periode
            $table->unique(['kontrak_id', 'periode']);
            
            // Set default value for status
            $table->string('status')->default('belum_dibayar')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penggajians', function (Blueprint $table) {
            $table->dropUnique(['kontrak_id', 'periode']);
            
            $table->string('status')->change();
        });
    }
};
