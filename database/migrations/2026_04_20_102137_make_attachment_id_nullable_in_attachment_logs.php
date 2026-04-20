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
        Schema::table('attachment_logs', function (Blueprint $table) {
            $table->dropForeign(['attachment_id']);
            $table->unsignedBigInteger('attachment_id')->nullable()->change();
            $table->foreign('attachment_id')->references('id')->on('task_attachments')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attachment_logs', function (Blueprint $table) {
            $table->dropForeign(['attachment_id']);
            $table->unsignedBigInteger('attachment_id')->nullable(false)->change();
            $table->foreign('attachment_id')->references('id')->on('task_attachments')->onDelete('cascade');
        });
    }
};
