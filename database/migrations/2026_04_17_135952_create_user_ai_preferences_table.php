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
        Schema::create('user_ai_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('default_provider')->default('gemini'); // gemini, openai, etc.
            $table->text('api_key')->nullable(); // Para custom keys cifradas si lo permitimos
            $table->boolean('smart_matching_opt_in')->default(true);
            $table->boolean('mood_tracking_enabled')->default(true);
            $table->json('ai_settings')->nullable(); // Opciones adicionales de configuración
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_ai_preferences');
    }
};
