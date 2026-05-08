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
        // 1. Añadir columnas a la tabla de usuarios
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_approved')->default(true)->after('is_admin');
            $table->integer('invitations_left')->default(5)->after('is_approved');
        });

        // 2. Crear tabla de invitaciones
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('email')->nullable();
            $table->string('code')->unique();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_approved', 'invitations_left']);
        });
    }
};
