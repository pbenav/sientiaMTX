<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable()->comment('Descripción en Markdown');
            $table->integer('duration_minutes')->default(15)->comment('Duración en minutos');
            $table->integer('slot_duration_minutes')->nullable()->comment('Override del tramo mínimo, null = hereda del setting');
            $table->integer('max_per_slot')->nullable()->comment('Override del máximo por tramo, null = hereda del setting');
            $table->decimal('price', 10, 2)->nullable()->comment('Precio del servicio, null = gratuito');
            $table->boolean('price_visible')->default(false)->comment('Mostrar precio en el portal público');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_services');
    }
};
