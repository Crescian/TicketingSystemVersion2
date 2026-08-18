<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('classification_reminded_at')->nullable()->after('sla_breached_notified_at');
            $table->timestamp('report_review_reminded_at')->nullable()->after('classification_reminded_at');
        });

        Schema::table('reclassification_requests', function (Blueprint $table) {
            $table->timestamp('reminded_at')->nullable()->after('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['classification_reminded_at', 'report_review_reminded_at']);
        });

        Schema::table('reclassification_requests', function (Blueprint $table) {
            $table->dropColumn('reminded_at');
        });
    }
};
