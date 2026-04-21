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
        Schema::create('services', function (Blueprint $row) {
            $row->id();
            $row->foreignId('team_id')->constrained()->onDelete('cascade');
            $row->string('name');
            $row->string('url')->nullable();
            $row->string('icon')->nullable();
            $row->enum('status', ['up', 'down', 'unstable'])->default('up');
            $row->text('description')->nullable();
            $row->timestamp('status_updated_at')->nullable();
            $row->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
