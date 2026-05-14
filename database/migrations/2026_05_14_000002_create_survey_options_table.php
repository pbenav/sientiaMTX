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
        if (!Schema::hasTable('survey_options')) {
            Schema::create('survey_options', function (Blueprint $table) {
                $table->id();
                $table->foreignId('survey_id')->constrained()->onDelete('cascade');
                $table->string('label');
                $table->text('description')->nullable();
                $table->integer('order')->default(0);
                $table->string('color')->nullable()->default('#3B82F6');
                $table->boolean('is_other')->default(false);
                $table->timestamps();
                
                $table->index('survey_id');
            });
        }
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_options');
    }
};
