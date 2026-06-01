<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->nullable()->constrained('appointment_services')->nullOnDelete()
                  ->comment('null = bloquea todos los servicios del tramo');
            $table->datetime('start_datetime');
            $table->datetime('end_datetime');
            $table->string('reason')->nullable()->comment('Motivo del bloqueo (visible al ciudadano si notify=true)');
            $table->boolean('notify_affected')->default(true)->comment('Notificar por email a citas ya reservadas en ese tramo');
            $table->timestamps();

            $table->index(['user_id', 'start_datetime', 'end_datetime']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_blocks');
    }
};
