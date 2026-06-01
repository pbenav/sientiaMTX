<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('localizador')->unique()->comment('MTXCITA-XXXXXXXX');
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('El miembro que atiende');
            $table->foreignId('service_id')->constrained('appointment_services')->onDelete('cascade');
            $table->foreignId('visitor_id')->constrained('appointment_visitors')->onDelete('cascade');
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->integer('slot_duration_minutes')->default(15);
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed', 'blocked'])
                  ->default('confirmed');
            $table->text('member_notes')->nullable()->comment('Notas internas del miembro');
            $table->foreignId('task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->foreignId('expediente_id')->nullable()->constrained('expedientes')->nullOnDelete();
            $table->string('google_event_id')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'appointment_date']);
            $table->index(['service_id', 'appointment_date', 'appointment_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
