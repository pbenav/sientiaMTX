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
        if (!Schema::hasTable('telegram_group_members')) {
            Schema::create('telegram_group_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('team_id')->constrained()->onDelete('cascade');
                $table->string('telegram_user_id');
                $table->string('username')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->timestamp('last_seen_at')->useCurrent();
                $table->timestamps();

                // Asegurar unicidad de un usuario por equipo
                $table->unique(['team_id', 'telegram_user_id'], 'telegram_member_team_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_group_members');
    }
};
