<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Http\UploadedFile;
use App\Models\Task;

class StoreTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $team = $this->route('team');
        return auth()->user()->can('view', $team) && auth()->user()->can('create', [Task::class, $team]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $team = $this->route('team');

        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,critical',
            'urgency' => 'required|in:low,medium,high,critical',
            'scheduled_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'assigned_to' => 'nullable|array',
            'assigned_groups' => 'nullable|array',
            'observations' => 'nullable|string',
            'parent_id' => [
                'nullable',
                Rule::exists('tasks', 'id')->where('team_id', $team->id),
            ],
            'visibility' => 'required|in:public,private',
            'is_autoprogrammable' => 'nullable|boolean',
            'autoprogram_settings' => 'nullable|array',
            'matrix_order' => 'nullable|integer|min:0',
            'skills' => 'nullable|array',
            'skills.*' => 'integer|exists:skills,id',
            'skill_id' => 'nullable|integer|exists:skills,id', // Legacy
            'service_id' => [
                'nullable',
                'integer',
                Rule::exists('services', 'id')->where('team_id', $team->id),
            ],
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:' . (UploadedFile::getMaxFilesize() / 1024),
            'assignment_mode' => 'nullable|string|in:shared,distributed',
            'expediente_id' => [
                'nullable',
                Rule::exists('expedientes', 'id')->where('team_id', $team->id),
            ],
            'is_timeline_locked' => 'nullable|boolean',
        ];
    }
}
