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
            $table->string('position')->nullable();
            $table->string('business_unit')->nullable();
            $table->string('company')->nullable();
            $table->string('department')->nullable();
        
            $table->string('level_request')->nullable();
            $table->string('employee_name')->nullable();

            $table->date('date_received')->nullable();
            $table->time('time_received')->nullable();
            $table->date('date_acknowledged')->nullable();
            $table->time('time_acknowledged')->nullable();
        
            $table->string('method')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
