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
        Schema::table('task_attachments', function (Blueprint $table) {
            $table->string('storage_provider')->default('local')->after('file_path');
            $table->string('provider_file_id')->nullable()->after('storage_provider');
            $table->string('web_view_link')->nullable()->after('provider_file_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_attachments', function (Blueprint $table) {
            $table->dropColumn(['storage_provider', 'provider_file_id', 'web_view_link']);
        });
    }
};
