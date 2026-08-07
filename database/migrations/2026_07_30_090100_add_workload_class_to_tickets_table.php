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
            // The workload class applied at Classify (& Assign) time — decoupled from
            // sla_rules so picking one can override this ticket's response/resolution
            // minutes without touching the subcategory's catalog defaults.
            $table->uuid('workload_class_id')->nullable();

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
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['workload_class_id']);
            $table->dropColumn('workload_class_id');
        });
    }
};
