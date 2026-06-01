<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_visitors', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('dni')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->text('observations')->nullable();
            $table->boolean('consent_email')->default(false)->comment('Autoriza recibir email de confirmación');
            $table->boolean('consent_data')->default(false)->comment('Autoriza tratamiento de datos');
            $table->boolean('consent_legal')->default(false)->comment('Acepta condiciones legales y cookies');
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_visitors');
    }
};
