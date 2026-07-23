<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use Illuminate\Support\Str;

use App\Traits\HasUuid;
use App\Traits\HandlesEisenhowerMatrix;
use App\Traits\TaskScopes;
use App\Traits\TaskTracking;
use App\Traits\TaskVisibility;
use App\Traits\TaskOperations;
use App\Traits\TaskNotifications;
use App\Traits\TaskConversion;

/**
 * Modelo legacy de Tarea (obsoleto, migrado a Activity).
 *
 * @deprecated Use \App\Models\Activity instead. This model is kept for backward compatibility.
 * Logs a warning every time it is accessed.
 *
 * Representa una tarea del sistema con:
 *   - Prioridad, urgencia, estado, progreso
 *   - Asignación a usuarios y grupos
 *   - Autoprogramación (recurrencia)
 *   - Integración con Google Tasks/Calendar
 *   - Kanban, Matriz Eisenhower, Gantt
 *   - Tracking de tiempo, adjuntos, calificaciones
 *
 * @property int $id ID de la tarea
 * @property int $team_id ID del equipo
 * @property string $title Título de la tarea
 * @property string|null $description Descripción
 * @property string $priority Prioridad (low, medium, high, critical)
 * @property bool $auto_priority Si la prioridad se actualiza automáticamente
 * @property string $urgency Urgencia (low, medium, high, critical)
 * @property string $status Estado de la tarea
 * @property \Carbon\Carbon|null $scheduled_date Fecha de programación
 * @property \Carbon\Carbon|null $due_date Fecha de vencimiento
 * @property \Carbon\Carbon|null $original_due_date Fecha original de vencimiento
 * @property int $created_by_id ID del creador
 * @property array|null $metadata Metadatos JSON
 * @property string|null $observations Observaciones
 * @property int|null $parent_id ID de la tarea padre
 * @property int|null $expediente_id ID del expediente
 * @property bool $is_template Si es plantilla maestra
 * @property int|null $assigned_user_id ID del usuario asignado
 * @property int $progress_percentage Porcentaje de progreso
 * @property string|null $visibility Visibilidad (public, semi-private, private)
 * @property string|null $google_task_id ID en Google Tasks
 * @property string|null $google_task_list_id ID de lista en Google Tasks
 * @property string|null $google_calendar_event_id ID de evento en Google Calendar
 * @property string|null $google_calendar_id ID de calendario en Google
 * @property \Carbon\Carbon|null $google_synced_at Fecha de sincronización con Google
 * @property bool $is_archived Si está archivada
 * @property int|null $kanban_column_id ID de columna Kanban
 * @property int|null $kanban_order Orden en Kanban
 * @property array|null $autoprogram_settings Configuración de autoprogramación
 * @property bool $is_out_of_skill_tree Si está fuera del árbol de habilidades
 * @property int $cognitive_load Carga cognitiva (0-10)
 * @property bool $is_backstage Si es tarea de backstage
 * @property int $impact_human_metric Métrica de impacto humano
 * @property int|null $skill_id ID de habilidad/especialidad
 * @property int|null $matrix_order Orden en Matriz Eisenhower
 * @property int|null $service_id ID de servicio
 * @property bool $is_autoprogrammable Si es autoprogramable
 * @property bool $is_timeline_locked Si la línea de tiempo está bloqueada
 * @property string $uuid Identificador único
 * @property-read \Carbon\Carbon $created_at
 * @property-read \Carbon\Carbon $updated_at
 * @property-read \Carbon\Carbon|null $deleted_at
 *
 * @property-read \App\Models\Service|null $service
 * @property-read \App\Models\Team $team
 * @property-read \App\Models\User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TaskAssignment> $assignments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $assignedTo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Group> $assignedGroups
 * @property-read \App\Models\CalendarEvent|null $calendarEvent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TaskHistory> $histories
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TaskTag> $tags
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Skill> $skills
 * @property-read \App\Models\Skill|null $skill
 * @property-read \App\Models\Expediente|null $expediente
 * @property-read \App\Models\Task|null $parent
 * @property-read \App\Models\Appointment|null $appointment
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $children
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $instances
 * @property-read \App\Models\User|null $assignedUser
 * @property-read \App\Models\ForumThread|null $forumThread
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TaskPrivateNote> $privateNotes
 * @property-read \App\Models\TaskPrivateNote|null $currentPrivateNote
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TimeLog> $timeLogs
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TaskAttachment> $attachments
 * @property-read \App\Models\KanbanColumn|null $kanbanColumn
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TaskRating> $ratings
 * @property-read \App\Models\Activity|null $activity
 * @property-read bool $is_effectively_private
 * @property-read string $privacy_level
 * @property-read int $progress
 * @property-read bool $is_instance
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TaskAttachment> $all_attachments
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Task extends Model
{
    use HasFactory, SoftDeletes, HandlesEisenhowerMatrix, HasUuid, TaskScopes, TaskTracking, TaskVisibility, TaskOperations, TaskNotifications, TaskConversion;

    /**
     * Boot del modelo: registra un hook 'retrieved' que registra un warning
     * en el log cada vez que se accede a este modelo legacy.
     * Ayuda a rastrear y eliminar referencias obsoletas.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Alerta de depuración para rastrear accesos al modelo legacy Task
        static::retrieved(function ($task) {
            try {
                if (request()) {
                    $url = request()->fullUrl();
                    $action = request()->route()?->getActionName() ?? 'N/A';
                    \Illuminate\Support\Facades\Log::warning("LEGACY TASK ACCESS: Se ha accedido al modelo obsoleto Task (ID: {$task->id}). URL: {$url} | Action: {$action}");
                } else {
                    \Illuminate\Support\Facades\Log::warning("LEGACY TASK ACCESS: Se ha accedido al modelo obsoleto Task (ID: {$task->id}) desde CLI / Background Job.");
                }
            } catch (\Exception $e) {
                // Silenciar para no interferir con la ejecución normal
            }
        });
    }

    /**
     * Atributos rellenables (mass assignable).
     */
    protected $fillable = [
        'team_id',
        'title',
        'description',
        'priority',
        'auto_priority',
        'urgency',
        'status',
        'scheduled_date',
        'due_date',
        'original_due_date',
        'created_by_id',
        'metadata',
        'observations',
        'parent_id',
        'expediente_id',
        'is_template',
        'assigned_user_id',
        'progress_percentage',
        'visibility',
        'google_task_id',
        'google_task_list_id',
        'google_calendar_event_id',
        'google_calendar_id',
        'google_synced_at',
        'is_archived',
        'kanban_column_id',
        'kanban_order',
        'autoprogram_settings',
        'is_out_of_skill_tree',
        'cognitive_load',
        'is_backstage',
        'impact_human_metric',
        'skill_id',
        'matrix_order',
        'service_id',
        'is_autoprogrammable',
        'is_timeline_locked',
    ];
 
    /**
     * Casts de atributos a tipos nativos de PHP.
     */
    protected $casts = [
        'metadata' => 'array',
        'due_date' => 'datetime',
        'scheduled_date' => 'datetime',
        'original_due_date' => 'datetime',
        'google_synced_at' => 'datetime',
        'is_archived' => 'boolean',
        'autoprogram_settings' => 'array',
        'is_out_of_skill_tree' => 'boolean',
        'cognitive_load' => 'integer',
        'is_backstage' => 'boolean',
        'impact_human_metric' => 'integer',
        'skill_id' => 'integer',
        'service_id' => 'integer',
        'is_autoprogrammable' => 'boolean',
        'auto_priority' => 'boolean',
        'is_timeline_locked' => 'boolean',
    ];

    // ─── Relaciones ───────────────────────────────────────────────────────────

    /**
     * Relación de pertenencia al servicio asociado.
     *
     * @return BelongsTo<\App\Models\Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Relación de pertenencia al equipo.
     *
     * @return BelongsTo<\App\Models\Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Relación de pertenencia al usuario creador.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Relación uno-a-muchos de asignaciones de tarea.
     *
     * @return HasMany<\App\Models\TaskAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }

    /**
     * Relación muchos-a-muchos con usuarios asignados.
     * Incluye datos de la asignación (fecha, asignador) vía pivot.
     * Ordenados por nombre de usuario.
     *
     * @return BelongsToMany<\App\Models\User, $this>
     */
    public function assignedTo(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_assignments')
            ->withPivot('assigned_at', 'assigned_by_id')
            ->withTimestamps()
            ->orderBy('name');
    }

    /**
     * Relación muchos-a-muchos con grupos asignados.
     * Incluye datos de la asignación (fecha, asignador) vía pivot.
     *
     * @return BelongsToMany<\App\Models\Group, $this>
     */
    public function assignedGroups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'task_assignments')
            ->withPivot('assigned_at', 'assigned_by_id')
            ->withTimestamps();
    }

    /**
     * Relación uno-a-cero-o-uno con evento de calendario.
     *
     * @return HasOne<\App\Models\CalendarEvent, $this>
     */
    public function calendarEvent(): HasOne
    {
        return $this->hasOne(CalendarEvent::class);
    }

    /**
     * Relación uno-a-muchos de registros de historial de tarea.
     *
     * @return HasMany<\App\Models\TaskHistory, $this>
     */
    public function histories(): HasMany
    {
        return $this->hasMany(TaskHistory::class);
    }

    /**
     * Relación uno-a-muchos de etiquetas de tarea.
     *
     * @return HasMany<\App\Models\TaskTag, $this>
     */
    public function tags(): HasMany
    {
        return $this->hasMany(TaskTag::class);
    }

    /**
     * Relación muchos-a-muchos con habilidades/especialidades.
     * Usa la tabla activity_skills (compartida con Activity) por compatibilidad con la migración V2.
     *
     * @return BelongsToMany<\App\Models\Skill, $this>
     */
    public function skills(): BelongsToMany
    {
        // La tabla pivot fue renombrada de skill_task -> activity_skills en la migración
        // 2026_07_06_172548_rename_skill_task_to_activity_skills, con FK activity_id.
        // El modelo Task mantiene compatibilidad pasiva; las skills se gestionan desde Activity.
        return $this->belongsToMany(Skill::class, 'activity_skills', 'activity_id', 'skill_id');
    }

    /**
     * Relación de pertenencia a habilidad/especialidad.
     *
     * @return BelongsTo<\App\Models\Skill, $this>
     */
    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    /**
     * Relación de pertenencia al expediente asociado.
     *
     * @return BelongsTo<\App\Models\Expediente, $this>
     */
    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    /**
     * Relación de pertenencia a la tarea padre (dependencia/subtarea).
     *
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    /**
     * Relación uno-a-cero-o-uno con cita asociada.
     *
     * @return HasOne<\App\Models\Appointment, $this>
     */
    public function appointment(): HasOne
    {
        return $this->hasOne(Appointment::class, 'task_id');
    }

    /**
     * Relación uno-a-muchos de subtareas o tareas dependientes.
     * Ordenadas por título y nombre del usuario asignado.
     *
     * @return HasMany<self, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id')
            ->orderBy('title')
            ->orderByRaw('(SELECT name FROM users WHERE users.id = tasks.assigned_user_id)');
    }

    /**
     * Relación uno-a-muchos de instancias (ocurrencias no-template).
     * Filtra solo las que no son plantillas, ordenadas por título.
     *
     * @return HasMany<self, $this>
     */
    public function instances(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id')->where('is_template', false)->orderBy('title');
    }

    /**
     * Relación de pertenencia al usuario asignado directamente.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * Relación uno-a-cero-o-uno con el hilo de foro asociado.
     *
     * @return HasOne<\App\Models\ForumThread, $this>
     */
    public function forumThread(): HasOne
    {
        return $this->hasOne(ForumThread::class);
    }

    /**
     * Relación uno-a-muchos de notas privadas de tarea.
     *
     * @return HasMany<\App\Models\TaskPrivateNote, $this>
     */
    public function privateNotes()
    {
        return $this->hasMany(TaskPrivateNote::class);
    }

    /**
     * Obtiene la nota privada del usuario autenticado actual.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<\App\Models\TaskPrivateNote, $this>
     */
    public function currentPrivateNote()
    {
        return $this->hasOne(TaskPrivateNote::class)->where('user_id', auth()->id());
    }

    // Time Tracking Relationships

    /**
     * Relación uno-a-muchos de registros de tiempo rastreado.
     *
     * @return HasMany<\App\Models\TimeLog, $this>
     */
    public function timeLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class);
    }

    /**
     * Relación polimórfica uno-a-muchos de adjuntos.
     *
     * @return MorphMany<\App\Models\TaskAttachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(TaskAttachment::class, 'attachable');
    }

    /**
     * Relación de pertenencia a la columna Kanban.
     *
     * @return BelongsTo<\App\Models\KanbanColumn, $this>
     */
    public function kanbanColumn(): BelongsTo
    {
        return $this->belongsTo(KanbanColumn::class);
    }

    /**
     * Relación uno-a-muchos de calificaciones de tarea.
     *
     * @return HasMany<\App\Models\TaskRating, $this>
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(TaskRating::class);
    }
}
