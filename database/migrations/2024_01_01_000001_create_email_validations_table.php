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
        Schema::create('email_validations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('visitor_id')->nullable();
            $table->foreign('visitor_id')->references('id')->on('appointment_visitors')->onDelete('set null');
            $table->string('email', 255);
            $table->string('domain', 255);
            $table->boolean('is_valid')->default(false);
            $table->string('verification_method')->nullable(); // smtp, mx_only, none
            $table->text('verification_reason')->nullable();
            $table->integer('mx_count')->default(0);
            $table->string('mx_host')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['email', 'domain']);
            $table->index('is_valid');
            $table->index('verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_validations');
    }
};
