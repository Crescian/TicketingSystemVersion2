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
            // Off by default — IT Admin opts a subcategory in explicitly (password
            // resets, account unlocks, known quick fixes) rather than every
            // subcategory being fair game for Helpdesk to keep for themselves.
            $table->boolean('helpdesk_resolvable')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sla_rules', function (Blueprint $table) {
            $table->dropColumn('helpdesk_resolvable');
        });
    }
};
