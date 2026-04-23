<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('task_attachments', function (Blueprint $table) {
            $table->unsignedBigInteger('attachable_id')->nullable()->after('id');
            $table->string('attachable_type')->nullable()->after('attachable_id');
            $table->index(['attachable_id', 'attachable_type']);
        });

        // Migrate existing data
        DB::table('task_attachments')->whereNotNull('task_id')->update([
            'attachable_id' => DB::raw('task_id'),
            'attachable_type' => 'App\Models\Task'
        ]);

        Schema::table('task_attachments', function (Blueprint $table) {
            // Drop foreign key if it exists
            try {
                $table->dropForeign(['task_id']);
            } catch (\Exception $e) {
                // Ignore if not exists
            }
            $table->dropColumn('task_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_attachments', function (Blueprint $table) {
            $table->unsignedBigInteger('task_id')->nullable()->after('attachable_type');
        });

        DB::table('task_attachments')->where('attachable_type', 'App\Models\Task')->update([
            'task_id' => DB::raw('attachable_id')
        ]);

        Schema::table('task_attachments', function (Blueprint $table) {
            $table->dropIndex(['attachable_id', 'attachable_type']);
            $table->dropColumn(['attachable_id', 'attachable_type']);
        });
    }
};
