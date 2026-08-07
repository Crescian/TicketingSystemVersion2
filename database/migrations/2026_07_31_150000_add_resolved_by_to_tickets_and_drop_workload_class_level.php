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
            // Who actually performed the resolution (as opposed to who validated it,
            // classified it, or closed & notified) — drives the Service Report's
            // "Level of Request" (L1 Helpdesk / L2 Technician / L3 Supervisor-IT Admin /
            // L4 Manager), and is a more reliable signal for that than the workload
            // class, which isn't always set.
            $table->uuid('resolved_by')->nullable();

            $table->foreign('resolved_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });

        // Superseded by the resolved_by-role-based Level of Request above — this
        // column and its admin UI never actually got used for anything else.
        Schema::table('workload_classes', function (Blueprint $table) {
            $table->dropColumn('level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['resolved_by']);
            $table->dropColumn('resolved_by');
        });

        Schema::table('workload_classes', function (Blueprint $table) {
            $table->string('level')->nullable();
        });
    }
};
