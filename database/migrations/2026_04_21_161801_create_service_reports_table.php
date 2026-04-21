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
        Schema::create('service_reports', function (Blueprint $row) {
            $row->id();
            $row->foreignId('service_id')->constrained()->onDelete('cascade');
            $row->foreignId('user_id')->constrained()->onDelete('cascade');
            $row->enum('type', ['up', 'down']);
            $row->text('details')->nullable();
            $row->boolean('is_verified')->default(false);
            $row->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_reports');
    }
};
