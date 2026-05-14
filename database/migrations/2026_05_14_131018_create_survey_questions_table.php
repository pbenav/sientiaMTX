<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create survey_questions table
        Schema::create('survey_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['single_choice', 'multiple_choice', 'rating', 'text'])->default('single_choice');
            $table->integer('order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->timestamps();
        });

        // 2. Adjust survey_options
        Schema::table('survey_options', function (Blueprint $table) {
            $table->foreignId('question_id')->nullable()->after('survey_id')->constrained('survey_questions')->onDelete('cascade');
        });

        // 3. Adjust survey_votes
        Schema::table('survey_votes', function (Blueprint $table) {
            $table->foreignId('question_id')->nullable()->after('survey_id')->constrained('survey_questions')->onDelete('cascade');
        });

        // 4. Data Migration: Create a question for each existing survey
        $surveys = DB::table('surveys')->get();
        foreach ($surveys as $survey) {
            $questionId = DB::table('survey_questions')->insertGetId([
                'survey_id' => $survey->id,
                'title' => $survey->title, // Use title as the first question
                'description' => $survey->description,
                'type' => $survey->type,
                'order' => 0,
                'is_required' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update options to point to this new question
            DB::table('survey_options')->where('survey_id', $survey->id)->update(['question_id' => $questionId]);
            
            // Update votes to point to this new question
            DB::table('survey_votes')->where('survey_id', $survey->id)->update(['question_id' => $questionId]);
        }

        // 5. Cleanup legacy columns and constraints
        Schema::table('survey_options', function (Blueprint $table) {
            $table->dropForeign(['survey_id']);
            $table->dropColumn('survey_id');
            $table->unsignedBigInteger('question_id')->nullable(false)->change();
        });

        Schema::table('survey_votes', function (Blueprint $table) {
            $table->dropForeign(['survey_id']);
            $table->dropColumn('survey_id');
            $table->unsignedBigInteger('question_id')->nullable(false)->change();
        });

        Schema::table('surveys', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->enum('type', ['single_choice', 'multiple_choice', 'rating', 'text'])->default('single_choice');
        });

        Schema::table('survey_votes', function (Blueprint $table) {
            $table->foreignId('survey_id')->nullable()->constrained()->onDelete('cascade');
        });

        Schema::table('survey_options', function (Blueprint $table) {
            $table->foreignId('survey_id')->nullable()->constrained()->onDelete('cascade');
        });

        Schema::dropIfExists('survey_questions');
    }
};
