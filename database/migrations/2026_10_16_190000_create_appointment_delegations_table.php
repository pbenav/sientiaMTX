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
        Schema::create('appointment_delegations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->comment('Titular de la agenda de citas');
            $table->foreignId('delegate_id')->constrained('users')->cascadeOnDelete()->comment('Secretario/a que gestiona la agenda');
            $table->timestamps();

            $table->unique(['team_id', 'user_id', 'delegate_id'], 'app_del_team_user_delegate_unique');
            $table->index(['team_id', 'delegate_id'], 'app_del_team_delegate_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_delegations');
    }
};
