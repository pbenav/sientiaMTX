<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('public_slug')->unique()->nullable()->comment('Slug personalizado para la URL pública');
            $table->string('display_name')->nullable()->comment('Nombre visible en el portal público');
            $table->boolean('is_public')->default(false)->comment('Aparece en el mapa/portal público');
            $table->text('welcome_text')->nullable()->comment('Texto de bienvenida (Markdown)');
            $table->text('legal_text')->nullable()->comment('Texto legal personalizado');
            $table->integer('default_slot_duration')->default(15)->comment('Duración por defecto del tramo en minutos');
            $table->integer('default_max_per_slot')->default(1)->comment('Máximo de citas simultáneas por tramo');
            $table->boolean('google_calendar_enabled')->default(false);
            $table->foreignId('default_expediente_id')->nullable()->constrained('expedientes')->nullOnDelete();
            $table->boolean('auto_create_task')->default(true)->comment('Crear tarea automáticamente por cada cita');
            $table->boolean('email_confirmation')->default(true)->comment('Enviar email de confirmación al ciudadano');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_settings');
    }
};
