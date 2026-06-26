<?php

namespace App\Services;

use App\Models\Team;
use App\Models\Task;
use App\Models\AttachmentLog;
use Illuminate\Support\Facades\DB;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskEventNotification;

class TaskService
{
    /**
     * Create a new task with all its assignments, instances, and attachments.
     */
    public function createTask(Team $team, array $validated, $requestFiles, ?string $driveAttachmentsJson): Task
    {
        return DB::transaction(function () use ($team, $validated, $requestFiles, $driveAttachmentsJson) {
            $hasAssignments = !empty($validated['assigned_to']) || !empty($validated['assigned_groups']);
            $assignmentMode = $validated['assignment_mode'] ?? 'shared';
            $isTemplate = $hasAssignments && $assignmentMode === 'distributed';

            $task = $team->tasks()->create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'priority' => $validated['priority'],
                'urgency' => $validated['urgency'],
                'status' => 'pending',
                'scheduled_date' => $validated['scheduled_date'] ?? null,
                'due_date' => $validated['due_date'] ?? null,
                'original_due_date' => $validated['due_date'] ?? null,
                'created_by_id' => auth()->id(),
                'observations' => $validated['observations'] ?? null,
                'parent_id' => $validated['parent_id'] ?? null,
                'is_template' => $isTemplate,
                'visibility' => $validated['visibility'],
                'is_autoprogrammable' => $validated['is_autoprogrammable'] ?? false,
                'autoprogram_settings' => $validated['autoprogram_settings'] ?? null,
                'is_out_of_skill_tree' => $validated['is_out_of_skill_tree'] ?? false,
                'cognitive_load' => $validated['cognitive_load'] ?? 1,
                'is_backstage' => $validated['is_backstage'] ?? false,
                'service_id' => $validated['service_id'] ?? null,
                'expediente_id' => $validated['expediente_id'] ?? null,
                'is_timeline_locked' => $validated['is_timeline_locked'] ?? false,
            ]);

            // Sync Skills
            $skillIds = $validated['skills'] ?? ($validated['skill_id'] ?? null ? [$validated['skill_id']] : []);
            if (!empty($skillIds)) {
                $task->skills()->sync($skillIds);
            }

            // Upload Local Attachments
            $this->handleLocalAttachments($task, $team, $requestFiles);

            // Handle Drive Attachments
            $this->handleDriveAttachments($task, $driveAttachmentsJson);

            // Handle Assignments
            $this->handleAssignmentsForCreation($task, $team, $validated, $isTemplate, $skillIds);

            // Autoprogramming Trigger
            if ($task->is_autoprogrammable) {
                $settings = $task->autoprogram_settings;
                if (!isset($settings['next_occurrence_at'])) {
                    $settings['next_occurrence_at'] = ($task->scheduled_date ? $task->scheduled_date->toDateTimeString() : now()->toDateTimeString());
                    $task->update(['autoprogram_settings' => $settings]);
                }
                $task->autoWakeup();
            }

            $task->syncKanbanColumn();

            return $task;
        });
    }

    protected function handleLocalAttachments(Task $task, Team $team, ?array $files): void
    {
        if (empty($files)) return;

        $totalUploadSize = collect($files)->sum(fn($file) => $file->getSize());
        
        // Storage limit is checked in Controller before calling Service
        
        foreach ($files as $file) {
            $path = $file->store('attachments', 'public');
            $originalName = $file->getClientOriginalName();
            $datePrefix = date('Y-m-d-');
            $fileName = str_starts_with($originalName, $datePrefix) ? $originalName : $datePrefix . $originalName;

            $attachment = $task->attachments()->create([
                'user_id' => auth()->id(),
                'file_path' => $path,
                'file_name' => $fileName,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);

            AttachmentLog::create([
                'attachment_id' => $attachment->id,
                'user_id' => auth()->id(),
                'action' => 'upload',
                'metadata' => [
                    'original_name' => $originalName,
                    'size' => $file->getSize()
                ],
                'ip_address' => request()->ip()
            ]);
        }
    }

    protected function handleDriveAttachments(Task $task, ?string $driveAttachmentsJson): void
    {
        if (empty($driveAttachmentsJson)) return;

        $driveFiles = json_decode($driveAttachmentsJson, true);
        if (is_array($driveFiles)) {
            foreach ($driveFiles as $file) {
                $attachment = $task->attachments()->create([
                    'user_id' => auth()->id(),
                    'file_name' => $file['name'],
                    'file_path' => 'google_drive/' . $file['id'],
                    'file_size' => $file['size'] ?? 0,
                    'mime_type' => $file['mimeType'] ?? 'application/octet-stream',
                    'storage_provider' => 'google',
                    'provider_file_id' => $file['id'],
                    'web_view_link' => $file['webViewLink'],
                ]);

                AttachmentLog::create([
                    'attachment_id' => $attachment->id,
                    'user_id' => auth()->id(),
                    'action' => 'drive_migration',
                    'metadata' => [
                        'file_id' => $file['id'],
                        'source' => 'google_drive'
                    ],
                    'ip_address' => request()->ip()
                ]);
            }
        }
    }

    protected function handleAssignmentsForCreation(Task $task, Team $team, array $validated, bool $isTemplate, array $skillIds): void
    {
        $userIds = collect($validated['assigned_to'] ?? []);
        
        if (!empty($validated['assigned_groups'])) {
            foreach ($validated['assigned_groups'] as $groupId) {
                $group = $team->groups()->find($groupId);
                if ($group) {
                    $userIds = $userIds->merge($group->users->pluck('id'));
                }
                $task->assignments()->create([
                    'group_id' => $groupId,
                    'assigned_by_id' => auth()->id(),
                ]);
            }
        }

        if ($isTemplate && empty($userIds)) {
            $userIds->push($task->created_by_id);
        }

        if (in_array($task->created_by_id, $validated['assigned_to'] ?? [])) {
            $userIds->push($task->created_by_id);
        }

        $uniqueUserIds = $userIds->unique();

        foreach ($uniqueUserIds as $userId) {
            if ($userId) {
                $task->assignments()->create([
                    'user_id' => $userId,
                    'assigned_by_id' => auth()->id(),
                ]);
            }


            if ($isTemplate) {
                $this->createDistributedInstance($task, $team, $userId, $skillIds);
            } else {
                $this->notifyAssignedUser($task, $userId);
            }
        }
    }

    protected function createDistributedInstance(Task $task, Team $team, $userId, array $skillIds): void
    {
        $instance = $team->tasks()->create([
            'title' => $task->title,
            'description' => $task->description,
            'priority' => $task->priority,
            'urgency' => $task->urgency,
            'status' => 'pending',
            'scheduled_date' => $task->scheduled_date,
            'due_date' => $task->due_date,
            'original_due_date' => $task->due_date,
            'created_by_id' => $task->created_by_id,
            'observations' => null,
            'parent_id' => $task->id,
            'is_template' => false,
            'assigned_user_id' => $userId,
            'expediente_id' => $task->expediente_id,
            'visibility' => 'private',
            'is_out_of_skill_tree' => $task->is_out_of_skill_tree,
            'service_id' => $task->service_id,
            'cognitive_load' => $task->cognitive_load,
            'is_backstage' => $task->is_backstage,
            'skill_id' => $task->skill_id,
        ]);

        if (!empty($skillIds)) {
            $instance->skills()->sync($skillIds);
        }

        $this->notifyAssignedUser($instance, $userId);
    }

    protected function notifyAssignedUser(Task $task, $userId): void
    {
        if ((int)$userId !== (int)auth()->id()) {
            try {
                \App\Models\User::find($userId)?->notify(new TaskAssignedNotification($task, auth()->user()));
            } catch (\Exception $e) {
                \Log::error("Failed to send TaskAssignedNotification: " . $e->getMessage());
            }
        }
    }
    /**
     * Update an existing task with all its assignments, instances, and history.
     */
    public function updateTask(Task $task, Team $team, array $validated, array $requestInputs, bool $isCoordinator): Task
    {
        return DB::transaction(function () use ($task, $team, $validated, $requestInputs, $isCoordinator) {
            $oldValues = $task->getAttributes();
            $oldProgress = (int)$task->progress_percentage;

            $task->update([
                'title' => array_key_exists('title', $validated) ? $validated['title'] : $task->title,
                'description' => array_key_exists('description', $validated) ? $validated['description'] : $task->description,
                'priority' => array_key_exists('priority', $validated) ? $validated['priority'] : $task->priority,
                'urgency' => array_key_exists('urgency', $validated) ? $validated['urgency'] : $task->urgency,
                'status' => array_key_exists('status', $validated) ? $validated['status'] : $task->status,
                'scheduled_date' => array_key_exists('scheduled_date', $validated) ? $validated['scheduled_date'] : $task->scheduled_date,
                'due_date' => array_key_exists('due_date', $validated) ? $validated['due_date'] : $task->due_date,
                'parent_id' => array_key_exists('parent_id', $validated) ? $validated['parent_id'] : $task->parent_id,
                'observations' => array_key_exists('observations', $validated) ? $validated['observations'] : $task->observations,
                'progress_percentage' => array_key_exists('progress_percentage', $validated) ? $validated['progress_percentage'] : $task->progress_percentage,
                'created_by_id' => array_key_exists('created_by_id', $validated) ? $validated['created_by_id'] : $task->created_by_id,
                'visibility' => $validated['visibility'] ?? $task->visibility,
                'is_autoprogrammable' => $validated['is_autoprogrammable'] ?? $task->is_autoprogrammable,
                'autoprogram_settings' => $validated['autoprogram_settings'] ?? $task->autoprogram_settings,
                'service_id' => array_key_exists('service_id', $validated) ? $validated['service_id'] : $task->service_id,
                'expediente_id' => array_key_exists('expediente_id', $validated) ? $validated['expediente_id'] : $task->expediente_id,
                'is_timeline_locked' => $validated['is_timeline_locked'] ?? $task->is_timeline_locked,
            ]);

            $skillIds = $requestInputs['skills'] ?? ($requestInputs['skill_id'] ?? null ? [$requestInputs['skill_id']] : []);
            if (isset($requestInputs['skills']) || isset($requestInputs['skill_id'])) {
                $task->skills()->sync($skillIds);
                if ($task->is_template) {
                    foreach($task->instances as $inst) {
                        $inst->skills()->sync($skillIds);
                    }
                }
            }

            $newProgress = (int)$task->progress_percentage;
            if ($newProgress >= 50 && $oldProgress < 50) {
                 $task->notifyCreatorAndCoordinators(new TaskEventNotification($task, 'milestone_50'));
            }
            if ($newProgress >= 75 && $oldProgress < 75) {
                 $task->notifyCreatorAndCoordinators(new TaskEventNotification($task, 'milestone_75'));
            }

            if ($task->parent_id) {
                $currentParent = $task->parent;
                while ($currentParent) {
                    $currentParent->update(['progress_percentage' => $currentParent->progress]);
                    $currentParent = $currentParent->parent;
                }
            }

            if ($task->is_template) {
                $task->instances()->update([
                    'priority' => $task->priority,
                    'urgency' => $task->urgency,
                    'due_date' => $task->due_date,
                    'original_due_date' => $task->due_date,
                    'expediente_id' => $task->expediente_id,
                    'created_by_id' => $task->created_by_id,
                ]);
            }

            $newValues = $task->getAttributes();
            $changes = array_diff_assoc($newValues, $oldValues);

            if (!empty($changes)) {
                $task->histories()->create([
                    'user_id' => auth()->id(),
                    'action' => 'updated',
                    'old_values' => $oldValues,
                    'new_values' => $newValues,
                ]);
            }

            if (isset($requestInputs['title']) && $isCoordinator) {
                $this->handleAssignmentsForUpdate($task, $team, $requestInputs);
            }

            if ($task->is_autoprogrammable) {
                $settings = $task->autoprogram_settings;
                if (!isset($settings['next_occurrence_at']) || $task->wasChanged('scheduled_date')) {
                    $settings['next_occurrence_at'] = ($task->scheduled_date ? $task->scheduled_date->toDateTimeString() : now()->toDateTimeString());
                    $task->update(['autoprogram_settings' => $settings]);
                }
                $task->autoWakeup();
            }

            $task->syncKanbanColumn();

            return $task;
        });
    }

    protected function handleAssignmentsForUpdate(Task $task, Team $team, array $inputs): void
    {
        $previousUserIds = $task->assignedTo()->pluck('users.id')->toArray();
        $assignedTo = array_filter((array) ($inputs['assigned_to'] ?? []), fn($v) => !is_null($v) && $v !== '');
        $assignedGroups = array_filter((array) ($inputs['assigned_groups'] ?? []), fn($v) => !is_null($v) && $v !== '');

        $userIds = collect($assignedTo);
        foreach ($assignedGroups as $groupId) {
            $group = $team->groups()->find($groupId);
            if ($group) {
                $userIds = $userIds->merge($group->users->pluck('id'));
            }
        }
        $uniqueUserIds = $userIds->unique();

        $task->assignments()->delete();

        foreach ($uniqueUserIds as $userId) {
            if ($userId) {
                $task->assignments()->create([
                    'user_id' => $userId,
                    'assigned_by_id' => auth()->id(),
                ]);
            }
        }

        foreach ($assignedGroups as $groupId) {
            $task->assignments()->create([
                'group_id' => $groupId,
                'assigned_by_id' => auth()->id(),
            ]);
        }

        $hasAssignments = $uniqueUserIds->isNotEmpty() || !empty($assignedGroups);
        $assignmentMode = $inputs['assignment_mode'] ?? 'shared';
        $isTemplate = $hasAssignments && $assignmentMode === 'distributed';
        
        $task->is_template = $isTemplate;
        $task->save();

        if (!$isTemplate) {
            $newUserIds = $uniqueUserIds->diff($previousUserIds);
            foreach ($newUserIds as $userId) {
                $this->notifyAssignedUser($task, $userId);
            }
        }
        
        if ($isTemplate) {
            $task->instances()
                ->whereNotNull('assigned_user_id')
                ->where(function($q) {
                    $q->whereNull('metadata->is_occurrence')
                      ->orWhere('metadata->is_occurrence', '!=', true);
                })
                ->whereNotIn('assigned_user_id', $uniqueUserIds)
                ->get()
                ->each
                ->delete();

            foreach ($uniqueUserIds as $userId) {
                if (!$task->instances()->where('assigned_user_id', $userId)->exists()) {
                    $this->createDistributedInstance($task, $team, $userId, []);
                }
            }
        } else {
            $task->instances()
                ->whereNotNull('assigned_user_id')
                ->where(function($q) {
                    $q->whereNull('metadata->is_occurrence')
                      ->orWhere('metadata->is_occurrence', '!=', true);
                })
                ->get()
                ->each
                ->delete();
            $task->assigned_user_id = null;
        }
        $task->save();
    }

    /**
     * Merge a source task into a target task, centralizing all relationships.
     */
    public function mergeTasks(Task $sourceTask, Task $targetTask): void
    {
        DB::transaction(function () use ($sourceTask, $targetTask) {
            // 1. Combine content additively if source brings something new
            $cleanSourceDesc = trim(strip_tags($sourceTask->description ?? ''));
            $cleanTargetDesc = trim(strip_tags($targetTask->description ?? ''));
            if ($cleanSourceDesc !== '' && strpos($cleanTargetDesc, $cleanSourceDesc) === false) {
                $targetTask->description = ($targetTask->description ?? '') . "\n\n--- [Fusionado desde: {$sourceTask->title}] ---\n\n" . $sourceTask->description;
            }

            $cleanSourceObs = trim(strip_tags($sourceTask->observations ?? ''));
            $cleanTargetObs = trim(strip_tags($targetTask->observations ?? ''));
            if ($cleanSourceObs !== '' && strpos($cleanTargetObs, $cleanSourceObs) === false) {
                $targetTask->observations = ($targetTask->observations ?? '') . "\n\n--- [Fusionado desde: {$sourceTask->title}] ---\n\n" . $sourceTask->observations;
            }
            $targetTask->save();

            // 2. Reassign subtasks
            $sourceTask->children()->update(['parent_id' => $targetTask->id]);

            // 3. Transfer Time Logs
            $sourceTask->timeLogs()->update(['task_id' => $targetTask->id]);

            // 4. Transfer Morphic Attachments
            \App\Models\TaskAttachment::where('attachable_type', Task::class)
                ->where('attachable_id', $sourceTask->id)
                ->update(['attachable_id' => $targetTask->id]);
            
            \App\Models\TaskAttachment::where('attachable_type', 'App\Models\Task')
                ->where('attachable_id', $sourceTask->id)
                ->update(['attachable_id' => $targetTask->id]);

            // 5. Transfer Private Notes
            $sourceTask->privateNotes()->update(['task_id' => $targetTask->id]);

            // 6. Transfer Kudos
            \App\Models\Kudo::where('task_id', $sourceTask->id)->update(['task_id' => $targetTask->id]);

            // 7. Transfer Task History trail
            $sourceTask->histories()->update(['task_id' => $targetTask->id]);

            // 8. Merge Tags without duplication
            foreach($sourceTask->tags as $tag) {
                $exists = $targetTask->tags()->where('tag', $tag->tag)->exists();
                if (!$exists) {
                    $tag->update(['task_id' => $targetTask->id]);
                }
            }

            // 9. Merge User/Group Assignments ensuring uniqueness
            foreach($sourceTask->assignments as $assignment) {
                $existsQuery = $targetTask->assignments();
                if ($assignment->user_id) {
                    $existsQuery->where('user_id', $assignment->user_id);
                } else {
                    $existsQuery->where('group_id', $assignment->group_id);
                }
                
                if (!$existsQuery->exists()) {
                    $assignment->update(['task_id' => $targetTask->id]);
                }
            }

            // 10. Forum Thread resolution: Transfer messages or adopt orphan thread
            $sourceThread = $sourceTask->forumThread;
            if ($sourceThread) {
                $targetThread = $targetTask->forumThread;
                if ($targetThread) {
                    $sourceThread->messages()->update(['forum_thread_id' => $targetThread->id]);
                    $sourceThread->delete();
                } else {
                    $sourceThread->update(['task_id' => $targetTask->id]);
                }
            }

            // 11. Calendar Event adoption
            $sourceCal = $sourceTask->calendarEvent;
            if ($sourceCal) {
                if (!$targetTask->calendarEvent()->exists()) {
                    $sourceCal->update(['task_id' => $targetTask->id]);
                } else {
                    $sourceCal->delete();
                }
            }

            // 12. Finally destroy source task tracking the history for destination
            $targetTask->histories()->create([
                'user_id' => auth()->id(),
                'action' => 'task_merged',
                'notes' => "Tarea ID #{$sourceTask->id} ('{$sourceTask->title}') ha sido fusionada en esta tarea."
            ]);

            $sourceTask->delete();
        });
    }

    /**
     * Update multiple tasks at once and return the count and completed tasks.
     * 
     * @return array ['count' => int, 'completedTasks' => Task[]]
     */
    public function bulkUpdateTasks(Team $team, array $taskIds, string $field, $value, $user): array
    {
        $tasks = Task::whereIn('id', $taskIds)
            ->where('team_id', $team->id)
            ->get();

        $updatedCount = 0;
        $completedTasks = [];

        foreach ($tasks as $task) {
            if ($user->can('update', $task)) {
                $oldValue = $task->{$field};
                
                // Special check for assignment: update visibility if needed
                if ($field === 'assigned_user_id' && (int)$value !== $user->id && $task->visibility === 'private') {
                    $task->visibility = 'public';
                }

                $task->update([$field => $value]);
                
                // Track completed tasks for gamification
                if ($field === 'status' && $value === 'completed' && $oldValue !== 'completed') {
                    $completedTasks[] = $task;
                }

                // If collaborator assigned, notify
                if ($field === 'assigned_user_id' && (int)$value !== $user->id && $oldValue != $value) {
                    try {
                        \App\Models\User::find($value)?->notify(new \App\Notifications\TaskAssignedNotification($task, $user));
                    } catch (\Exception $e) { /* Ignore notification errors */ }
                }

                // Log history
                $task->histories()->create([
                    'user_id' => $user->id,
                    'action' => 'bulk_updated',
                    'old_values' => [$field => $oldValue],
                    'new_values' => [$field => $value],
                    'notes' => "Actualización masiva de {$field}"
                ]);

                $updatedCount++;
            }
        }

        return ['count' => $updatedCount, 'completedTasks' => $completedTasks];
    }
}
