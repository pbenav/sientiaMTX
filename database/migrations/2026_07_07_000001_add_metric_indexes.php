<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $columns = DB::select("SHOW INDEX FROM {$table}");
        foreach ($columns as $column) {
            if ($column->Key_name === $indexName) {
                return true;
            }
        }
        return false;
    }

    /**
     * Add an index only if it doesn't exist.
     */
    private function addIndexIfNotExists(string $table, array $columns, string $indexName): void
    {
        if (!$this->indexExists($table, $indexName)) {
            Schema::table($table, function (Blueprint $table) use ($columns, $indexName) {
                $table->index($columns, $indexName);
            });
        }
    }

    public function up(): void
    {
        // ===================================================================
        // Activities: status is longtext (JSON), need generated column
        // ===================================================================
        $hasStatusStr = DB::select("SHOW COLUMNS FROM activities WHERE Field = ?", ['status_str']);
        if (empty($hasStatusStr)) {
            DB::statement("
                ALTER TABLE activities
                ADD COLUMN status_str VARCHAR(50) GENERATED ALWAYS AS (
                    JSON_UNQUOTE(JSON_EXTRACT(status, '$.status'))
                ) STORED
            ");
        }

        $this->addIndexIfNotExists('activities', ['team_id', 'status_str', 'updated_at'], 'idx_activities_team_status_date');
        $this->addIndexIfNotExists('activities', ['created_by_id', 'due_date'], 'idx_activities_user_date');
        // priority_status index skipped: status is longtext (JSON) - use status_str generated column instead

        // Time logs
        $this->addIndexIfNotExists('time_logs', ['user_id', 'start_at'], 'idx_time_logs_user_date');
        $this->addIndexIfNotExists('time_logs', ['task_id'], 'idx_time_logs_activity');

        // Mood logs
        $this->addIndexIfNotExists('user_mood_logs', ['user_id', 'created_at'], 'idx_mood_logs_user_date');

        // Appointments: status is enum, safe to index directly
        $this->addIndexIfNotExists('appointments', ['appointment_date', 'status'], 'idx_appointments_date_status');
        $this->addIndexIfNotExists('appointments', ['service_id'], 'idx_appointments_service');
        $this->addIndexIfNotExists('appointments', ['visitor_id'], 'idx_appointments_visitor');

        // Survey votes
        $this->addIndexIfNotExists('survey_votes', ['user_id', 'voted_at'], 'idx_survey_votes_survey_user');
        $this->addIndexIfNotExists('survey_votes', ['question_id'], 'idx_survey_votes_question');

        // Metric snapshots
        $this->addIndexIfNotExists('metric_snapshots', ['user_id', 'snapshot_date'], 'idx_snapshots_user_date');
        $this->addIndexIfNotExists('metric_snapshots', ['team_id', 'snapshot_date'], 'idx_snapshots_team_date');

        // Metric alerts
        $this->addIndexIfNotExists('metric_alerts', ['user_id', 'is_read', 'created_at'], 'idx_alerts_user_unread');
        $this->addIndexIfNotExists('metric_alerts', ['team_id', 'is_read', 'severity'], 'idx_alerts_team_unresolved');

        // Metric reports
        $this->addIndexIfNotExists('metric_reports', ['user_id', 'type', 'period_start'], 'idx_reports_user_type_date');

        // Kudos
        $this->addIndexIfNotExists('kudos', ['from_user_id'], 'idx_kudos_sender');
        $this->addIndexIfNotExists('kudos', ['to_user_id'], 'idx_kudos_receiver');

        // Gamification logs
        $this->addIndexIfNotExists('gamification_logs', ['user_id', 'created_at'], 'idx_gamification_user_date');
        $this->addIndexIfNotExists('gamification_logs', ['type'], 'idx_gamification_type');

        // Forum threads: no status column, only team index
        $this->addIndexIfNotExists('forum_threads', ['team_id'], 'idx_forum_team_status');

        // Forum messages
        $this->addIndexIfNotExists('forum_messages', ['forum_thread_id', 'created_at'], 'idx_forum_thread_date');

        // Chat messages
        $this->addIndexIfNotExists('chat_messages', ['chat_group_id', 'created_at'], 'idx_chat_group_date');
        $this->addIndexIfNotExists('chat_messages', ['sender_id', 'created_at'], 'idx_chat_sender_date');

        // Calendar events
        $this->addIndexIfNotExists('calendar_events', ['team_id', 'start_time'], 'idx_calendar_user_date');

        // Quick notes
        $this->addIndexIfNotExists('quick_notes', ['user_id', 'created_at'], 'idx_notes_user_date');
    }

    public function down(): void
    {
        // Only drop indexes we created (not those that existed before)
        // We use DB::statement directly with the index name we specified
        $indexes = [
            'activities' => ['idx_activities_team_status_date', 'idx_activities_user_date'],
            'time_logs' => ['idx_time_logs_user_date', 'idx_time_logs_activity'],
            'user_mood_logs' => ['idx_mood_logs_user_date'],
            'appointments' => ['idx_appointments_date_status', 'idx_appointments_service', 'idx_appointments_visitor'],
            'survey_votes' => ['idx_survey_votes_survey_user', 'idx_survey_votes_question'],
            'metric_snapshots' => ['idx_snapshots_user_date', 'idx_snapshots_team_date'],
            'metric_alerts' => ['idx_alerts_user_unread', 'idx_alerts_team_unresolved'],
            'metric_reports' => ['idx_reports_user_type_date'],
            'kudos' => ['idx_kudos_sender', 'idx_kudos_receiver'],
            'gamification_logs' => ['idx_gamification_user_date', 'idx_gamification_type'],
            'forum_threads' => ['idx_forum_team_status'],
            'forum_messages' => ['idx_forum_thread_date'],
            'chat_messages' => ['idx_chat_group_date', 'idx_chat_sender_date'],
            'calendar_events' => ['idx_calendar_user_date'],
            'quick_notes' => ['idx_notes_user_date'],
        ];

        foreach ($indexes as $table => $idxList) {
            foreach ($idxList as $index) {
                // Only drop if the index exists AND is not a foreign key index
                $columns = DB::select("SHOW INDEX FROM {$table}");
                foreach ($columns as $col) {
                    if ($col->Key_name === $index && (int)$col->Seq_in_index === 1 && $col->Non_unique == 1) {
                        // Check if it's not a primary or unique key
                        try {
                            DB::statement("ALTER TABLE {$table} DROP INDEX {$index}");
                        } catch (\Exception $e) {
                            // Skip if drop fails (e.g., needed by foreign key)
                        }
                        break;
                    }
                }
            }
        }

        // Drop the generated column if it exists
        if (Schema::hasColumn('activities', 'status_str')) {
            Schema::table('activities', function (Blueprint $table) {
                $table->dropColumn('status_str');
            });
        }
    }
};
