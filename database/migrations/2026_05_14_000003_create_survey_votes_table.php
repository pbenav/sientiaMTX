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
        Schema::create('survey_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->onDelete('cascade');
            $table->foreignId('option_id')->constrained('survey_options')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('text_value')->nullable();
            $table->timestamp('voted_at')->nullable();
            $table->timestamps();
            
            $table->index(['survey_id', 'user_id']);
        });

        // Pivot table for the belongsToMany relationship in Survey model
        Schema::create('survey_user_votes', function (Blueprint $table) {
            $table->foreignId('survey_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->primary(['survey_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_votes');
        Schema::dropIfExists('survey_user_votes');
    }
};
