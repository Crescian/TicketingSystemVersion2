<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('helpdesk_ack_reminded_at')->nullable()->after('requestor_confirmation_reminded_at');
            $table->timestamp('helpdesk_classification_reminded_at')->nullable()->after('helpdesk_ack_reminded_at');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['helpdesk_ack_reminded_at', 'helpdesk_classification_reminded_at']);
        });
    }
};
