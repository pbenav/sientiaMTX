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
        if (!Schema::hasTable('expediente_related')) {
            Schema::create('expediente_related', function (Blueprint $table) {
                $table->id();
                $table->foreignId('expediente_id')->constrained('expedientes')->onDelete('cascade');
                $table->foreignId('related_id')->constrained('expedientes')->onDelete('cascade');
                $table->unique(['expediente_id', 'related_id']);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expediente_related');
    }
};
