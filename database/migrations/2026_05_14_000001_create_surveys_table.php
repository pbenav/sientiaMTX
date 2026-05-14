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
        if (!Schema::hasTable('surveys')) {
            Schema::create('surveys', function (Blueprint $table) {
                $table->id();
                $table->uuid()->unique();
                $table->foreignId('team_id')->constrained()->onDelete('cascade');
                $table->foreignId('created_by_id')->constrained('users')->onDelete('cascade');
                $table->string('title');
                $table->text('description')->nullable();
                $table->enum('type', ['single_choice', 'multiple_choice', 'rating', 'text'])->default('single_choice');
                $table->boolean('is_active')->default(true);
                $table->boolean('allow_multiple_votes')->default(false);
                $table->boolean('show_results_before_voting')->default(false);
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('published_at')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();
                
                $table->index(['team_id', 'is_active']);
                $table->index('uuid');
            });
        }
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};
