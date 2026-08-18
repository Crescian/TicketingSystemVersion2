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
            // Which role's queue an unassigned ticket is currently sitting in
            // (e.g. 'Helpdesk', 'Supervisor - Support Specialist',
            // 'Supervisor - IT Admin', 'Manager') while status is
            // For Acknowledgment/Classified and assigned_to is still null.
            // Once assigned_to is set, dashboards disambiguate via
            // assignedTo.role instead (see App\Support\TicketReportProgress::forRoles).
            $table->string('pending_role')->nullable()->after('assigned_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('pending_role');
        });
    }
};
