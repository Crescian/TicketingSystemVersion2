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
        Schema::table('sla_rules', function (Blueprint $table) {
            // Default workload class for this subcategory — its response/resolution
            // minutes seed the rule's own times (and stay synced when the class is
            // edited), and Classify auto-selects it when this subcategory is picked.
            $table->uuid('workload_class_id')->nullable()->after('priority');

            $table->foreign('workload_class_id')
                ->references('id')
                ->on('workload_classes')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sla_rules', function (Blueprint $table) {
            $table->dropForeign(['workload_class_id']);
            $table->dropColumn('workload_class_id');
        });
    }
};
