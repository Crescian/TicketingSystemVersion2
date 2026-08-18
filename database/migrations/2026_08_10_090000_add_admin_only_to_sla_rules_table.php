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
        Schema::table('sla_rules', function (Blueprint $table) {
            // Off by default — IT Admin opts a subcategory in explicitly for work
            // that is genuinely L3-only (e.g. domain/AD, server maintenance).
            // Routes classify() straight to Supervisor - IT Admin, skipping the
            // Support Supervisor queue entirely.
            $table->boolean('admin_only')->default(false)->after('helpdesk_resolvable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sla_rules', function (Blueprint $table) {
            $table->dropColumn('admin_only');
        });
    }
};
