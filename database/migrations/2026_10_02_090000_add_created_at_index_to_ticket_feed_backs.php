<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // CREATE INDEX CONCURRENTLY can't run inside a transaction.
    public $withinTransaction = false;

    public function up(): void
    {
        // The executive dashboard's CSAT stats and Employee Feedback panel all
        // filter/sort by created_at on every page load and 30s poll. Built
        // CONCURRENTLY so feedback inserts aren't blocked while it builds.
        if (DB::getDriverName() !== 'pgsql') {
            Schema::table('ticket_feed_backs', fn (Blueprint $table) => $table->index('created_at'));
            return;
        }

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS ticket_feed_backs_created_at_index ON ticket_feed_backs (created_at)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            Schema::table('ticket_feed_backs', fn (Blueprint $table) => $table->dropIndex(['created_at']));
            return;
        }

        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS ticket_feed_backs_created_at_index');
    }
};
