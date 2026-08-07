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
        Schema::table('tickets', function (Blueprint $table) {
            // The moment the specialist acknowledged the assignment — stops the
            // Response Time SLA. Several controllers were already writing this key
            // via update([...]), but it silently no-op'd (mass-assignment guard drops
            // unfillable keys) since neither this column nor a $fillable entry existed.
            $table->timestamp('tech_acknowledged_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('tech_acknowledged_at');
        });
    }
};
