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
        Schema::create('workload_classes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');                 // e.g. Quick Fix, Standard, Complex, Major
            $table->text('typical_application')->nullable();

            // Targets stored in business minutes (see App\Support\BusinessClock) so the
            // resolution deadline correctly skips nights/weekends instead of counting
            // straight wall-clock time.
            $table->unsignedInteger('response_minutes');
            $table->unsignedInteger('resolution_minutes')->nullable();

            // Human-readable targets as written in the workload matrix — kept verbatim
            // ("Within 1 Business Day", "Based on Approved Project Timeline") since they
            // don't always reduce cleanly back from a raw minute count.
            $table->string('response_label');
            $table->string('resolution_label');

            // True for classes like Project/Planned Activity and Vendor Dependent, whose
            // resolution target has no fixed preset — Helpdesk/Supervisor must type the
            // agreed number of minutes in at classification time instead of it auto-filling.
            $table->boolean('requires_manual_resolution')->default(false);

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workload_classes');
    }
};
