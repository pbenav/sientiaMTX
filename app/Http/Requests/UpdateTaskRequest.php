<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Task;

class UpdateTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $team = $this->route('team');
        $task = $this->route('task');
        return auth()->user()->can('view', $team) && auth()->user()->can('update', $task);
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
            'status' => 'required|in:pending,in_progress,completed,cancelled,blocked',
            'scheduled_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'assigned_to' => 'nullable|array',
            'assigned_groups' => 'nullable|array',
            'observations' => 'nullable|string',
            'parent_id' => [
                'nullable',
                Rule::exists('tasks', 'id')->where('team_id', $team->id),
            ],
            'progress_percentage' => 'nullable|integer|min:0|max:100',
            'created_by_id' => 'nullable|exists:users,id',
            'visibility' => 'required|in:public,private',
            'is_autoprogrammable' => 'nullable|boolean',
            'autoprogram_settings' => 'nullable|array',
            'skill_id' => 'nullable|integer|exists:skills,id',
            'service_id' => [
                'nullable',
                'integer',
                Rule::exists('services', 'id')->where('team_id', $team->id),
            ],
            'assignment_mode' => 'nullable|string|in:shared,distributed',
            'expediente_id' => [
                'nullable',
                Rule::exists('expedientes', 'id')->where('team_id', $team->id),
            ],
            'is_timeline_locked' => 'nullable|boolean',
        ];
    }
}
