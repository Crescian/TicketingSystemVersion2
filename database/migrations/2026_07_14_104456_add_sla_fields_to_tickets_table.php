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
            $table->uuid('sla_category_id')->nullable();
            $table->string('subcategory_name')->nullable();

            $table->timestamp('sla_due_at')->nullable();
            $table->timestamp('sla_risk_notified_at')->nullable();
            $table->timestamp('sla_breached_notified_at')->nullable();

            $table->foreign('sla_category_id')
                ->references('id')
                ->on('sla_categories')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['sla_category_id']);
            $table->dropColumn([
                'sla_category_id',
                'subcategory_name',
                'sla_due_at',
                'sla_risk_notified_at',
                'sla_breached_notified_at',
            ]);
        });
    }
};
