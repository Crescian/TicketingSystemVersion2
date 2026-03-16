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
        Schema::create('escalations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id');
            $table->integer('escalation_level');
            $table->uuid('escalated_by');
            $table->uuid('previous_tech_id')->nullable();
            $table->uuid('reassigned_to')->nullable();
            $table->string('reason');
            $table->string('resolution_notes')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->foreign('ticket_id')
                ->references('id')
                ->on('tickets')
                ->onDelete('cascade');

            $table->foreign('escalated_by')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('previous_tech_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('reassigned_to')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('escalations');
    }
};
