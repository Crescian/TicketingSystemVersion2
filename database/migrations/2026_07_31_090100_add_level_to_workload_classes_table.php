<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('workload_classes', function (Blueprint $table) {
            // "Level of Request" (L1-L5) for the Service Report PDF — mapped 1:1 from
            // workload class (Quick Fix=L1 ... Project/Vendor=L5) rather than a
            // separately-picked value, since it's the same underlying complexity tier.
            $table->string('level')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workload_classes', function (Blueprint $table) {
            $table->dropColumn('level');
        });
    }
};
