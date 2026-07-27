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
        Schema::create('appointment_slot_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('appointment_services')->cascadeOnDelete();
            $table->date('date');
            $table->string('time'); // format H:i
            $table->integer('extra_capacity')->default(0);
            $table->timestamps();
            
            $table->unique(['service_id', 'date', 'time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_slot_overrides');
    }
};
