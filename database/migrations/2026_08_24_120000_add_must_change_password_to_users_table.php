<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false)->after('active');
        });

        // Backfill: preserves current enforcement for any account still on the
        // shared default password. Previously RequirePasswordChange detected this
        // by checking Hash::check('password', ...) directly; this column replaces
        // that check going forward, so accounts already stuck on the default need
        // the flag set explicitly or they'd stop being prompted.
        User::query()->get(['id', 'password'])->each(function (User $user) {
            if (Hash::check('password', $user->password)) {
                $user->forceFill(['must_change_password' => true])->saveQuietly();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('must_change_password');
        });
    }
};
