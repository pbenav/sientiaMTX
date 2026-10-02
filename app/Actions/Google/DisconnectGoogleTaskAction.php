<?php

namespace App\Actions\Google;

use App\Models\Team;
use App\Models\User;
use App\Services\GoogleService;
use Illuminate\Database\Eloquent\Model;

class DisconnectGoogleTaskAction
{
    public function __construct(protected GoogleService $googleService) {}

    public function execute(User $user, Team $team, Model $task, bool $deleteInGoogle = false): array
    {
        $gTaskId = $task->google_task_id;
        
        $task->update(['google_task_id' => null]);

        if ($deleteInGoogle && $gTaskId) {
            try {
                $this->googleService->deleteTask('@default', $gTaskId);
                return ['success' => true, 'message' => __('tasks.disconnect_google_deleted')];
            } catch (\Exception $e) {
                if ($e->getCode() != 404) {
                    \Log::error("Error deleting task from Google: " . $e->getMessage());
                    return ['success' => false, 'message' => __('tasks.disconnect_google_error')];
                }
            }
        }

        return ['success' => true, 'message' => __('tasks.disconnect_google_success')];
    }
}
