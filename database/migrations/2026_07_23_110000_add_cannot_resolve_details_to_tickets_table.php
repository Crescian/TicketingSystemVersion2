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
            // Captured from the supervisor's "Cannot Resolve" form so the requestor can
            // see why their ticket wasn't fixed, instead of it only living in the
            // internal status-history notes.
            $table->text('cannot_resolve_findings')->nullable();
            $table->text('cannot_resolve_recommendation')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['cannot_resolve_findings', 'cannot_resolve_recommendation']);
        });
    }
};
