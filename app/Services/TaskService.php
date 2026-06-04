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
}
