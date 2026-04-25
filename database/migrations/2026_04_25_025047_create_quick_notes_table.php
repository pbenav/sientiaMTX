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
        Schema::create('quick_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('content')->nullable();
            $table->integer('position_x')->default(100);
            $table->integer('position_y')->default(100);
            $table->integer('width')->default(300);
            $table->integer('height')->default(300);
            $table->string('color')->default('#fef3c7'); // Yellow default
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_minimized')->default(false);
            $table->json('attachments')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quick_notes');
    }
};
