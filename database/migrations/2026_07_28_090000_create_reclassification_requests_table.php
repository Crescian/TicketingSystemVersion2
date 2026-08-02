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
        Schema::create('reclassification_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id');
            $table->uuid('requested_by');
            $table->uuid('reviewed_by')->nullable();

            $table->uuid('current_sla_category_id')->nullable();
            $table->string('current_subcategory_name')->nullable();
            $table->string('current_priority')->nullable();

            $table->uuid('proposed_sla_category_id');
            $table->string('proposed_subcategory_name');
            $table->string('proposed_priority');
            $table->integer('proposed_response_time_minutes');
            $table->integer('proposed_resolution_time_minutes');

            $table->string('reason');
            $table->string('status')->default('pending'); // pending | approved | rejected
            $table->string('review_notes')->nullable();

            $table->timestamp('requested_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->foreign('ticket_id')
                ->references('id')
                ->on('tickets')
                ->onDelete('cascade');

            $table->foreign('requested_by')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('reviewed_by')
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
        Schema::dropIfExists('reclassification_requests');
    }
};
