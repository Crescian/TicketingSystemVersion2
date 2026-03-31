<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sla_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');                          // e.g. "High Priority SLA"
            $table->string('priority');                      // High, Medium, Low
            $table->string('ticket_type')->nullable();       // Hardware, Software, etc. (null = all)
            $table->integer('response_time_hours');          // First response within X hours
            $table->integer('resolution_time_hours');        // Must be resolved within X hours
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_rules');
    }
};