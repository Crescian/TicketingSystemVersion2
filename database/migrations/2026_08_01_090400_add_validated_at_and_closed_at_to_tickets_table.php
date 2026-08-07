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
            // Supervisor validation timestamp/notes (validateResolution/
            // adminValidateResolution) and Helpdesk's "notified requestor of closure"
            // timestamp — all three were already being written via update([...]) but
            // silently no-op'd (mass-assignment guard drops unfillable keys) since
            // neither these columns nor $fillable entries existed.
            $table->timestamp('validated_at')->nullable();
            $table->text('validation_notes')->nullable();
            $table->timestamp('closed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['validated_at', 'validation_notes', 'closed_at']);
        });
    }
};
