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
            // Promoted from being buried inside a TicketStatusHistories note to a real
            // column — needed structured (not just as audit-trail text) for the Service
            // Report PDF's "Service Details / Action Taken" box.
            $table->text('resolution_notes')->nullable();

            // Captured on the Technician's Resolve modal — feeds the Service Report's
            // "Service Type" checkboxes (Onsite / Remote / Preventive).
            $table->string('service_type')->nullable();

            // Feed the Service Report's "Findings & Analysis" and "Other
            // Observation / Recommendation" boxes. Optional — not every resolution
            // has something further to note.
            $table->text('findings')->nullable();
            $table->text('recommendation')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['resolution_notes', 'service_type', 'findings', 'recommendation']);
        });
    }
};
