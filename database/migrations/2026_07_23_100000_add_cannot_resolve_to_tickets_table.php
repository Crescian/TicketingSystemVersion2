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
            // Set when a Supervisor - Support Specialist marks an escalated ticket as
            // investigated but not technically fixable, rather than resolved. Lets the
            // requestor-facing tracker distinguish a real fix from a closed-out
            // "cannot resolve" outcome instead of showing a misleading checkmark.
            $table->boolean('cannot_resolve')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('cannot_resolve');
        });
    }
};
