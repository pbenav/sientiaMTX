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
        Schema::create('team_invitations', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('team_roles')->cascadeOnDelete();
            $table->string('token')->unique()->nullable();
            $table->timestamps();

            $table->unique(['email', 'team_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_invitations');
    }
};
