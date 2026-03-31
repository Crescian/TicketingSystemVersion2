<?php
// Migration 1: create_sla_categories_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Drop old sla_rules if exists
        Schema::dropIfExists('sla_rules');

        // Categories (e.g. Hardware, Software, Network, Access Request)
        Schema::create('sla_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');                  // e.g. Hardware
            $table->string('icon')->nullable();       // e.g. bi-laptop
            $table->string('color')->nullable();      // e.g. #f5c842
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Subcategory SLA rules
        Schema::create('sla_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sla_category_id');
            $table->string('subcategory_name');           // e.g. Laptop
            $table->string('priority');                   // High, Medium, Low
            $table->decimal('response_time_minutes', 8, 1);   // supports 30, 60, 90...
            $table->decimal('resolution_time_minutes', 8, 1);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('sla_category_id')
                ->references('id')
                ->on('sla_categories')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_rules');
        Schema::dropIfExists('sla_categories');
    }
};