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
            $table->index('status');
            $table->index(['assigned_to', 'status']);
            $table->index('users_id');
            $table->index('created_at');
            $table->index('sla_category_id');
        });

        Schema::table('ticket_status_histories', function (Blueprint $table) {
            $table->index('ticket_id');
        });

        Schema::table('ticket_messages', function (Blueprint $table) {
            $table->index(['ticket_id', 'is_read']);
        });

        Schema::table('ticket_feed_backs', function (Blueprint $table) {
            $table->index('ticket_id');
        });

        Schema::table('ticket_attachments', function (Blueprint $table) {
            $table->index('ticket_id');
        });

        Schema::table('escalations', function (Blueprint $table) {
            $table->index('ticket_id');
            $table->index('resolved_at');
            $table->index('escalated_at');
        });

        Schema::table('reclassification_requests', function (Blueprint $table) {
            $table->index(['ticket_id', 'status']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['role_id', 'active']);
            $table->index('department_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['assigned_to', 'status']);
            $table->dropIndex(['users_id']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['sla_category_id']);
        });

        Schema::table('ticket_status_histories', function (Blueprint $table) {
            $table->dropIndex(['ticket_id']);
        });

        Schema::table('ticket_messages', function (Blueprint $table) {
            $table->dropIndex(['ticket_id', 'is_read']);
        });

        Schema::table('ticket_feed_backs', function (Blueprint $table) {
            $table->dropIndex(['ticket_id']);
        });

        Schema::table('ticket_attachments', function (Blueprint $table) {
            $table->dropIndex(['ticket_id']);
        });

        Schema::table('escalations', function (Blueprint $table) {
            $table->dropIndex(['ticket_id']);
            $table->dropIndex(['resolved_at']);
            $table->dropIndex(['escalated_at']);
        });

        Schema::table('reclassification_requests', function (Blueprint $table) {
            $table->dropIndex(['ticket_id', 'status']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role_id', 'active']);
            $table->dropIndex(['department_id']);
        });
    }
};
