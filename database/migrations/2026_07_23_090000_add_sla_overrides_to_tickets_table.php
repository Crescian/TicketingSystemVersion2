<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Effective SLA minutes for this ticket, set at Classify & Assign time.
            // Defaults to the matched SlaRule's values but can be overridden per-ticket
            // by the supervisor, decoupling this ticket's SLA target from the rule catalog.
            $table->unsignedInteger('response_time_minutes')->nullable();
            $table->unsignedInteger('resolution_time_minutes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['response_time_minutes', 'resolution_time_minutes']);
        });
    }
};
