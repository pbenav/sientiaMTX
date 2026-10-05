<?php

namespace App\Actions\Google;

use App\Models\Team;
use App\Models\User;
use App\Services\GoogleService;
use Illuminate\Database\Eloquent\Model;

class SyncTaskWithGoogleAction
{
    public function __construct(protected GoogleService $googleService) {}

    public function execute(User $user, Team $team, Model $task): array
    {
        // 1. If not exported yet, export it
        if (!$task->google_task_id) {
            $notes = ($task->description ?: '') . "\n\n";
            $notes .= "--- SientiaMTX Details ---\n";
            $notes .= "Quadrant: " . (method_exists($task, 'getQuadrant') ? $task->getQuadrant($task) : '') . "\n";
            $notes .= "Priority: " . strtoupper($task->priority ?? '') . "\n";
            $notes .= "Urgency: " . strtoupper($task->urgency ?? '') . "\n";
            $notes .= "Team: " . $team->name . "\n";

            $dateToUse = $task->due_date ?? $task->scheduled_date;

            $data = [
                'title' => $task->title,
                'notes' => trim($notes),
            ];

            if ($dateToUse) {
                $data['due'] = $dateToUse->toRfc3339String();
            }

            try {
                $taskId = $this->googleService->createTask($data);
                if ($taskId) {
                    $task->update(['google_task_id' => $taskId]);
                    return ['success' => true, 'message' => __('tasks.sync_google_success')];
                }
                return ['success' => false, 'message' => 'Error exporting task.'];
            } catch (\Exception $e) {
                \Log::error("Error exporting task to Google: " . $e->getMessage());
                return ['success' => false, 'message' => __('tasks.sync_google_error')];
            }
        }

        // 2. If already exported, sync status
        try {
            $gTask = $this->googleService->getTask('@default', $task->google_task_id);
            if ($gTask) {
                $statusMap = [
                    'needsAction' => 'pending',
                    'completed' => 'completed'
                ];
                $gStatus = $gTask->getStatus();
                if (isset($statusMap[$gStatus])) {
                    $taskStatus = is_array($task->status) ? ($task->status['value'] ?? '') : $task->status;
                    if ($taskStatus !== $statusMap[$gStatus]) {
                        $task->update(['status' => $statusMap[$gStatus]]);
                    }
                }
                return ['success' => true, 'message' => __('tasks.sync_google_success')];
            }
        } catch (\Exception $e) {
            if ($e->getCode() == 404) {
                $task->update([
                    'google_task_id' => null,
                    'status' => 'cancelled',
                    'cancellation_reason' => 'Eliminada en Google Tasks',
                ]);
                return ['success' => true, 'message' => __('tasks.sync_google_deleted')];
            }
            \Log::error("Error syncing task from Google: " . $e->getMessage());
        }

        return ['success' => false, 'message' => __('tasks.sync_google_error')];
    }
}
