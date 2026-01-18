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
        // Check if status column doesn't exist, then add it
        if (Schema::hasTable('cashbons') && !Schema::hasColumn('cashbons', 'status')) {
            Schema::table('cashbons', function (Blueprint $table) {
                $table->string('status')->default('belum_dibayar')->after('keterangan');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('cashbons') && Schema::hasColumn('cashbons', 'status')) {
            Schema::table('cashbons', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
