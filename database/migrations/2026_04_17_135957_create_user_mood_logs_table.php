<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('user_mood_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('task_id')->nullable()->constrained()->onDelete('set null'); // Opcional, si el ánimo se asocia a terminar una tarea
            $table->integer('energy_level'); // Ej: 1 a 5
            $table->string('mood_label')->nullable(); // Ej: cansado, motivado, frustrado
            $table->text('notes')->nullable(); // Anotaciones del usuario o de la IA
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_mood_logs');
    }
};
