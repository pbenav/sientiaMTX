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
        if (!Schema::hasTable('telegram_messages')) {
            Schema::create('telegram_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Null if from Telegram side
                $table->string('author_name');
                $table->text('text');
                $table->string('telegram_message_id')->nullable();
                $table->boolean('is_from_web')->default(false);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_messages');
    }
};
