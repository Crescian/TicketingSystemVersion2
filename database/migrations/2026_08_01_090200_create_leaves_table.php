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
        Schema::create('leaves', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Per-technician calendar consulted by App\Services\TicketScheduler
            // alongside App\Support\BusinessClock's weekend/holiday check, so "next
            // working day" scheduling and freeMinutesToday()/statusFor() skip a
            // technician's own approved leave days too.
            $table->uuid('technician_id');
            $table->foreign('technician_id')->references('id')->on('users')->onDelete('cascade');

            $table->date('date');
            $table->string('reason')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['technician_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
