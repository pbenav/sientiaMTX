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
        Schema::create('attachment_logs', function (Blueprint $schema) {
            $schema->id();
            $schema->foreignId('attachment_id')->constrained('task_attachments')->onDelete('cascade');
            $schema->foreignId('user_id')->constrained('users');
            $schema->string('action'); // upload, download, view, rename, drive_migration, deleted
            $schema->json('metadata')->nullable(); // For old_name, new_name, etc.
            $schema->string('ip_address')->nullable();
            $schema->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachment_logs');
    }
};
