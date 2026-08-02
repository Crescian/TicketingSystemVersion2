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
        // Was varchar(255) — but callers routinely build notes out of an
        // auto-generated summary plus a free-typed note (classify/assign,
        // resolve, escalate, etc.), which regularly exceeds 255 characters and
        // was hard-failing the whole action with a DB truncation error.
        Schema::table('ticket_status_histories', function (Blueprint $table) {
            $table->text('notes')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_status_histories', function (Blueprint $table) {
            $table->string('notes')->nullable()->change();
        });
    }
};
