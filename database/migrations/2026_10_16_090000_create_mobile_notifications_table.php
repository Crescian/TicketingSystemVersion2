<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// In-app inbox for staff on the mobile app — one row per notification a staff
// member receives (mirrors their "Action Needed"/SLA/FYI emails and new chat
// messages); also pushed to their phones. Employees keep their own feed
// (status history + messages on their requests).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('type', 40);
            $table->string('title');
            $table->text('body')->nullable();
            $table->uuid('ticket_id')->nullable();
            $table->uuid('concern_id')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_notifications');
    }
};
