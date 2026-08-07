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
            // Time-slot queue fields (see App\Services\TicketScheduler) — replace the old
            // "max 5 active tickets" workload gauge with a real business-hours schedule.
            $table->timestamp('scheduled_start')->nullable();
            $table->timestamp('scheduled_end')->nullable();
            $table->boolean('is_overtime')->default(false);

            // FIFO tie-breaker within the same priority tier, set once when a ticket first
            // enters a specialist's not-started queue. Deliberately not reusing `assigned_at`
            // — that column is written to in several controllers but was never added to
            // Tickets::$fillable or created by any migration, so it silently no-ops today.
            $table->timestamp('queued_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['scheduled_start', 'scheduled_end', 'is_overtime', 'queued_at']);
        });
    }
};
