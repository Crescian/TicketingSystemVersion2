<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Running total of minutes spent On Hold across every pause/resume
            // cycle — subtracted from started_at→resolved_at so "time spent" /
            // "actual resolution time" reflects only active work (see
            // Tickets::actualResolutionTime()).
            $table->unsignedInteger('total_hold_minutes')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('total_hold_minutes');
        });
    }
};
