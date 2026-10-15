<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// System concerns (bugs, feature requests, data fixes) that ICT staff send
// straight to the Developer role. Deliberately its own table rather than
// tickets, so these never enter SLA timers, support queues or executive reports.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('developer_concerns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference')->unique();
            $table->uuid('submitted_by');
            $table->string('subject');
            $table->text('details');
            $table->string('category');
            $table->string('urgency');
            $table->string('status');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('closing_note')->nullable();
            $table->timestamps();

            $table->foreign('submitted_by')->references('id')->on('users')->onDelete('cascade');
            $table->index('status');
            $table->index('submitted_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('developer_concerns');
    }
};
