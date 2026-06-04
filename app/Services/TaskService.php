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
            if (in_array($userId, $validated['assigned_to'] ?? [])) {
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

            if (isset($requestInputs['has_title']) && $isCoordinator) {
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

        $task->assignments()->delete();

        foreach ($assignedTo as $userId) {
            $task->assignments()->create([
                'user_id' => $userId,
                'assigned_by_id' => auth()->id(),
            ]);
        }

        foreach ($assignedGroups as $groupId) {
            $task->assignments()->create([
                'group_id' => $groupId,
                'assigned_by_id' => auth()->id(),
            ]);
        }

        $userIds = collect($assignedTo);
        foreach ($assignedGroups as $groupId) {
            $group = $team->groups()->find($groupId);
            if ($group) {
                $userIds = $userIds->merge($group->users->pluck('id'));
            }
        }
        $uniqueUserIds = $userIds->unique();

        $hasAssignments = !empty($assignedTo) || !empty($assignedGroups);
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
                ->where('metadata->is_occurrence', '!=', true)
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
                ->where('metadata->is_occurrence', '!=', true)
                ->get()
                ->each
                ->delete();
            $task->assigned_user_id = null;
        }
        $task->save();
    }
}
