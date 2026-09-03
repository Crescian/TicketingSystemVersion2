<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('needs_account_setup')->default(false)->after('must_change_password');
        });

        // Backfill: only Employees already mid-flow (forced onto a temp/default
        // password right now) get the combined org-info + password gate going
        // forward. Anyone who already cleared must_change_password before this
        // feature shipped is left alone, even if they never touched org info —
        // this is not meant to retroactively lock out already-active accounts.
        User::whereHas('role', fn ($q) => $q->where('role_name', 'Employee'))
            ->where('must_change_password', true)
            ->update(['needs_account_setup' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('needs_account_setup');
        });
    }
};
