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
        $mappings = [
            'uploaded' => 'draft',
            'editing' => 'draft',
            'under_review' => 'review',
            'reviewed' => 'approved',
            'completed' => 'approved',
            'published' => 'approved'
        ];

        DB::transaction(function () use ($mappings) {
            $activities = Activity::where('type', 'document')->get();
            foreach ($activities as $act) {
                $val = $act->status_value;
                if (isset($mappings[$val])) {
                    $status = $act->status;
                    $status['value'] = $mappings[$val];
                    $act->status = $status;
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
        // No down migration as this is a data cleanup
    }
};
