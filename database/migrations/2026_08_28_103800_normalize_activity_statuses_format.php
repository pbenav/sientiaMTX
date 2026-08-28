<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Activity;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {
            // Find all activities where status is stored as a raw JSON string (starts with double quotes)
            $activities = Activity::where('status', 'like', '"%')->get();

            foreach ($activities as $act) {
                // If the status attribute evaluates to a string (which is the case when it's stored as JSON string),
                // we normalize it to the structured array format ['value' => $status]
                if (is_string($act->status)) {
                    $act->status = ['value' => $act->status];
                    $act->saveQuietly();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse operation needed as this is data normalization
    }
};
