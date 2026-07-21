<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedTinyInteger('level')->nullable()->after('role_name');
        });

        // Backfill support-tier levels for existing roles: L1 Helpdesk, L2 Support
        // Specialist, L3 Supervisors/Admin, L4 Manager. Employee has no tier.
        DB::table('roles')->where('role_name', 'Helpdesk')->update(['level' => 1]);
        DB::table('roles')->where('role_name', 'IT Support Specialist')->update(['level' => 2]);
        DB::table('roles')->whereIn('role_name', [
            'Supervisor - Support Specialist',
            'IT Admin',
            'Supervisor - IT Admin',
        ])->update(['level' => 3]);
        DB::table('roles')->where('role_name', 'Manager')->update(['level' => 4]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('level');
        });
    }
};
