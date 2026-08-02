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
        Schema::create('holidays', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Org-wide calendar consulted by App\Support\BusinessClock alongside its
            // weekend check, so "next working day" scheduling skips holidays too.
            $table->date('date')->unique();
            $table->string('name');
            $table->string('type')->nullable(); // e.g. 'regular', 'special-non-working'

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
