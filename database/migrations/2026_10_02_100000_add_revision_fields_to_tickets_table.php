<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nullable, no default: a metadata-only change in Postgres, so the table lock
        // is momentary. Give up rather than queue writes behind a long transaction.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SET LOCAL lock_timeout = '5s'");
        }

        Schema::table('tickets', function (Blueprint $table) {
            // Set when a Supervisor sends a resolution back for revision; cleared when
            // the specialist resubmits. Lets Start keep the original SLA clock for a
            // revision that waited in the queue, and lets the dashboard show the reason.
            $table->timestamp('revision_requested_at')->nullable();
            $table->text('revision_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['revision_requested_at', 'revision_notes']);
        });
    }
};
