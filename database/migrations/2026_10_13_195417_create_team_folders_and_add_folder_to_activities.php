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
        Schema::create('team_folders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('team_folders')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color', 30)->default('violet');
            $table->string('icon', 50)->default('folder');
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'parent_id']);
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->foreignId('folder_id')
                ->nullable()
                ->after('expediente_id')
                ->constrained('team_folders')
                ->nullOnDelete();

            $table->index(['team_id', 'folder_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['folder_id']);
            $table->dropIndex(['team_id', 'folder_id']);
            $table->dropColumn('folder_id');
        });

        Schema::dropIfExists('team_folders');
    }
};
