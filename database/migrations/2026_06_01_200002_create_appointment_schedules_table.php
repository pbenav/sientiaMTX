<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 0=Domingo, 1=Lunes, ..., 6=Sábado (compatible con Carbon)
        Schema::create('appointment_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->nullable()->constrained('appointment_services')->nullOnDelete()
                  ->comment('null = aplica a todos los servicios del miembro');
            $table->unsignedTinyInteger('day_of_week')->comment('0=Dom,1=Lun,2=Mar,3=Mié,4=Jue,5=Vie,6=Sáb');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('slot_duration_minutes')->default(15);
            $table->integer('max_per_slot')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_schedules');
    }
};
