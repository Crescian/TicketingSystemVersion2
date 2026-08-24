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
        Schema::table('ticket_attachments', function (Blueprint $table) {
            $table->uuid('ticket_message_id')->nullable()->after('ticket_id');

            $table->foreign('ticket_message_id')
                ->references('id')
                ->on('ticket_messages')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_attachments', function (Blueprint $table) {
            $table->dropForeign(['ticket_message_id']);
            $table->dropColumn('ticket_message_id');
        });
    }
};
