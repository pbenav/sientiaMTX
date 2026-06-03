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
        Schema::table('appointment_settings', function (Blueprint $table) {
            $table->string('jitsi_domain', 100)->default('meet.jit.si')->after('email_confirmation')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointment_settings', function (Blueprint $table) {
            $table->dropColumn('jitsi_domain');
        });
    }
};
