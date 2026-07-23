<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Models;

use App\Traits\ActivityAccessors;
use App\Traits\ActivityConversion;
use App\Traits\HasUuid;
use App\Traits\HandlesEisenhowerMatrix;
use App\Traits\ActivityScopes;
use App\Traits\ActivityTracking;
use App\Traits\ActivityVisibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Modelo base universal de Actividad.
 *
 * Representa cualquier tipo de contenido estructurado dentro del sistema:
 * task, document, note, link, decision, meeting, reminder, custom...
 *
 * Todos los tipos comparten esta infraestructura:
 *   - Etiquetas, notas, historial, asignaciones, adjuntos, calificaciones
 *   - Posición en Kanban, Matriz Eisenhower y Gantt
 *   - Visibilidad, prioridad, fechas y estado flexible por tipo
 *
 * Para añadir un tipo nuevo: crear un modelo en app/Models/Activities/,
 * añadirlo a SUBTYPES y crear su partial de vista. Nada más.
 *
 * @property string $uuid Identificador único universal
 * @property int|null $team_id ID del equipo al que pertenece la actividad
 * @property int $created_by_id ID del usuario creador
 * @property int|null $parent_id ID de la actividad padre (para templates/ocurrencias)
 * @property int|null $expediente_id ID del expediente asociado
 * @property string $type Tipo de actividad (task, document, note, link, agreement, meeting, reminder)
 * @property string $title Título de la actividad
 * @property string|null $description Descripción detallada
 * @property array|null $status Estado (array con 'value', 'metadata', 'metadata_version')
 * @property array|null $metadata Metadatos JSON específicos por tipo
 * @property string|null $visibility Nivel de visibilidad (public, semi-private, private, null)
 * @property \Carbon\Carbon|null $due_date Fecha de vencimiento
 * @property \Carbon\Carbon|null $scheduled_date Fecha de programación
 * @property \Carbon\Carbon|null $original_due_date Fecha original de vencimiento (antes de reprogramación)
 * @property string $priority Prioridad (low, medium, high, critical)
 * @property bool $auto_priority Si la prioridad se actualiza automáticamente
 * @property int $progress_percentage Porcentaje de progreso (0-100)
 * @property int|null $kanban_column_id ID de la columna Kanban
 * @property int|null $kanban_order Orden en el Kanban
 * @property int|null $matrix_order Orden en la Matriz Eisenhower
 * @property bool $is_archived Si está archivada
 * @property bool $is_template Si es una plantilla maestra (Plan Maestro)
 * @property string|null $google_task_id ID de la tarea sincronizada en Google Tasks
 * @property string|null $google_task_list_id ID de la lista de Google Tasks
 * @property string|null $google_calendar_event_id ID del evento de Google Calendar
 * @property string|null $google_calendar_id ID del calendario de Google
 * @property \Carbon\Carbon|null $google_synced_at Fecha de última sincronización con Google
 * @property-read \Carbon\Carbon $created_at Fecha de creación
 * @property-read \Carbon\Carbon $updated_at Fecha de última actualización
 * @property-read \Carbon\Carbon|null $deleted_at Fecha de eliminación (soft delete)
 *
 * @property-read \App\Models\Team $team
 * @property-read \App\Models\User $creator
 * @property-read \App\Models\Expediente|null $expediente
 * @property-read \App\Models\Activity|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Activity> $children
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Activity> $instances
 * @property-read \App\Models\KanbanColumn|null $kanbanColumn
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityTag> $tags
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityHistory> $histories
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityAssignment> $assignments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $assignedTo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Group> $assignedGroups
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityRating> $ratings
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityNote> $notes
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityAttachment> $attachments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Skill> $skills
 * @property-read string $status_value Valor del estado
 * @property-read int $progress Porcentaje de progreso
 * @property-read string $urgency Nivel de urgencia
 * @property-read int|null $skill_id ID de habilidad/especialidad
 * @property-read int|null $service_id ID de servicio
 * @property-read string $type_icon Icono SVG del tipo
 * @property-read string $type_label Etiqueta en español del tipo
 * @property-read string $type_badge_color Color del badge del tipo
 * @property-read bool $is_autoprogrammable Si es autoprogramable
 * @property-read string $privacy_level Nivel de privacidad
 * @property-read float $avg_quality_score Puntaje promedio de calidad
 * @property-read \App\Models\Activity|null $convertedToActivity Actividad a la que fue convertida
 * @property-read \App\Models\Activity|null $convertedFromActivity Actividad de la que fue convertida
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityAttachment> $allAttachments
 * @property-read \App\Models\Activities\TaskActivity|null asSubtype Subtipo resuelto
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Activity extends Model
{
    use HasFactory, SoftDeletes, HasUuid, HandlesEisenhowerMatrix, ActivityScopes, ActivityAccessors, ActivityTracking, ActivityVisibility, ActivityConversion, \App\Traits\ActivityOccurrences;

    // ─── Tipos registrados ────────────────────────────────────────────────────

    /**
     * Mapa de tipos de actividad a sus clases de subtipo específicas.
     * Cada tipo tiene su propio modelo en App\Models\Activities\ para comportamiento específico.
     */
    public const SUBTYPES = [
        'task'      => \App\Models\Activities\TaskActivity::class,
        'document'  => \App\Models\Activities\DocumentActivity::class,
        'note'      => \App\Models\Activities\NoteActivity::class,
        'link'      => \App\Models\Activities\LinkActivity::class,
        'agreement'  => \App\Models\Activities\AgreementActivity::class,
        'meeting'   => \App\Models\Activities\MeetingActivity::class,
        'reminder'  => \App\Models\Activities\ReminderActivity::class,
    ];

    /**
     * Tipos que pueden aparecer en el Kanban (Flujo de trabajo / Estados).
     */
    public const KANBAN_TYPES = ['task', 'agreement'];

    /**
     * Tipos que pueden aparecer en la Matriz Eisenhower (Priorización por urgencia/importancia).
     */
    public const MATRIX_TYPES = ['task', 'agreement'];

    /**
     * Tipos que aparecen en el Gantt (Línea de tiempo / Fechas).
     */
    public const GANTT_TYPES  = ['task', 'meeting', 'reminder', 'document', 'agreement'];

    // ─── Atributos ────────────────────────────────────────────────────────────

    /**
     * Nombre de la tabla asociada.
     */
    protected $table = 'activities';

    /**
     * Atributos rellenables (mass assignable) para creación/actualización.
     */
    protected $fillable = [
        'uuid',
        'team_id',
        'created_by_id',
        'parent_id',
        'expediente_id',
        'type',
        'title',
        'description',
        'status',
        'metadata',
        'visibility',
        'due_date',
        'scheduled_date',
        'original_due_date',
        'priority',
        'auto_priority',
        'progress_percentage',
        'kanban_column_id',
        'kanban_order',
        'matrix_order',
        'is_archived',
        'is_template',
        'google_task_id',
        'google_task_list_id',
        'google_calendar_event_id',
        'google_calendar_id',
        'google_synced_at',
    ];

    /**
     * Casts de atributos a tipos nativos de PHP.
     */
    protected $casts = [
        'status'               => 'array',
        'metadata'             => 'array',
        'due_date'             => 'datetime',
        'scheduled_date'       => 'datetime',
        'original_due_date'    => 'datetime',
        'google_synced_at'     => 'datetime',
        'is_archived'          => 'boolean',
        'is_template'          => 'boolean',
        'auto_priority'        => 'boolean',
        'progress_percentage'  => 'integer',
    ];

    // ─── Relaciones principales ───────────────────────────────────────────────

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
     * Relación de pertenencia al expediente asociado.
     *
     * @return BelongsTo<\App\Models\Expediente, $this>
     */
    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    /**
     * Relación de pertenencia a la actividad padre (template/ocurrencia).
     *
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'parent_id');
    }

    /**
     * Relación uno-a-muchos de actividades hijas (ocurrencias/subtareas).
     * Ordenadas por fecha de creación.
     *
     * @return HasMany<self, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Activity::class, 'parent_id')->orderBy('created_at');
    }

    /**
     * Relación uno-a-muchos de instancias (ocurrencias no-template).
     * Filtra solo las que no son plantillas, ordenadas por título.
     *
     * @return HasMany<self, $this>
     */
    public function instances(): HasMany
    {
        return $this->hasMany(Activity::class, 'parent_id')->where('is_template', false)->orderBy('title');
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

    // ─── Infraestructura compartida ───────────────────────────────────────────

    /**
     * Relación uno-a-muchos de etiquetas de actividad.
     *
     * @return HasMany<\App\Models\ActivityTag, $this>
     */
    public function tags(): HasMany
    {
        return $this->hasMany(ActivityTag::class, 'activity_id');
    }

    /**
     * Relación uno-a-muchos de registros de historial de actividad.
     * Ordenados por más reciente.
     *
     * @return HasMany<\App\Models\ActivityHistory, $this>
     */
    public function histories(): HasMany
    {
        return $this->hasMany(ActivityHistory::class, 'activity_id')->latest();
    }

    /**
     * Relación uno-a-muchos de asignaciones de actividad.
     *
     * @return HasMany<\App\Models\ActivityAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(ActivityAssignment::class, 'activity_id');
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
        return $this->belongsToMany(User::class, 'activity_assignments', 'activity_id', 'user_id')
            ->withPivot('assigned_at', 'assigned_by_id', 'completed_at')
            ->withTimestamps()
            ->distinct()
            ->orderBy('name');
    }

    /**
     * Relación hasOneThrough para compatibilidad legacy.
     * Devuelve el primer/principal usuario asignado a la actividad.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOneThrough<\App\Models\User, $this>
     */
    public function assignedUser()
    {
        return $this->hasOneThrough(
            User::class,
            ActivityAssignment::class,
            'activity_id', // Foreign key on activity_assignments table
            'id',          // Foreign key on users table
            'id',          // Local key on activities table
            'user_id'      // Local key on activity_assignments table
        );
    }

    /**
     * Relación muchos-a-muchos con grupos asignados.
     * Incluye datos de la asignación (fecha, asignador) vía pivot.
     *
     * @return BelongsToMany<\App\Models\Group, $this>
     */
    public function assignedGroups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'activity_assignments', 'activity_id', 'group_id')
            ->withPivot('assigned_at', 'assigned_by_id')
            ->withTimestamps()
            ->distinct();
    }

    /**
     * Relación uno-a-muchos de calificaciones de actividad.
     *
     * @return HasMany<\App\Models\ActivityRating, $this>
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(ActivityRating::class, 'activity_id');
    }

    /**
     * Relación uno-a-muchos de notas de actividad.
     * Ordenadas por más reciente.
     *
     * @return HasMany<\App\Models\ActivityNote, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(ActivityNote::class, 'activity_id')->latest();
    }

    /**
     * Obtiene la nota privada para un usuario específico.
     *
     * @param int $userId ID del usuario
     * @return \App\Models\ActivityNote|null La nota privada o null si no existe
     */
    public function privateNoteFor(int $userId): ?ActivityNote
    {
        return $this->hasOne(ActivityNote::class, 'activity_id')
            ->where('user_id', $userId)
            ->where('visibility', 'private')
            ->first();
    }

    /**
     * Obtiene la nota privada del usuario autenticado actual.
     * Compatibilidad con Task.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<\App\Models\TaskPrivateNote, $this>
     */
    public function currentPrivateNote()
    {
        return $this->hasOne(\App\Models\TaskPrivateNote::class, 'task_id')->where('user_id', auth()->id());
    }

    /**
     * Relación uno-a-muchos de adjuntos de actividad.
     *
     * @return HasMany<\App\Models\ActivityAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(ActivityAttachment::class, 'activity_id');
    }

    // ─── Relaciones de especialidades y servicios (Task compat layer) ─────────

    /**
     * Relación muchos-a-muchos con habilidades/especialidades.
     *
     * @return BelongsToMany<\App\Models\Skill, $this>
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'activity_skills', 'activity_id', 'skill_id');
    }

    /**
     * Relación de pertenencia a habilidad/especialidad.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Skill, $this>
     */
    public function skill(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Skill::class, 'skill_id', 'id');
    }

    /**
     * Relación de pertenencia a servicio.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Service, $this>
     */
    public function service(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id', 'id');
    }

    /**
     * Determina si la actividad está bloqueada porque el servicio asociado está caído.
     *
     * @return bool True si service_id está definido, la relación service está cargada y service->status es 'down'
     */
    public function isBlockedByService(): bool
    {
        return $this->service_id && $this->service && $this->service->status === 'down';
    }

    // ─── Subtipo dinámico ─────────────────────────────────────────────────────

    /**
     * Determina si esta actividad es una instancia (ocurrencia generada a partir de un template).
     * Una instancia tiene padre y no es plantilla.
     *
     * @return bool True si tiene parent_id y no es template
     */
    public function isInstance(): bool
    {
        return !empty($this->parent_id) && !$this->is_template;
    }

    /**
     * Helper paramétrico para factory pattern (Activity polymorphism).
     * Convierte esta actividad en su subtipo específico (TaskActivity, DocumentActivity, etc.)
     * manteniendo todos los atributos originales.
     *
     * @return static La instancia del subtipo con los atributos del modelo actual
     */
    public function asSubtype(): static
    {
        $class = self::SUBTYPES[$this->type] ?? static::class;
        $model = new $class();
        $model->setRawAttributes($this->getRawOriginal(), true);
        $model->exists = $this->exists;
        return $model;
    }

    // ─── Estado y progreso ────────────────────────────────────────────────────

    /**
     * Determina si la actividad está completada.
     * Compara status_value contra múltiples estados semánticamente equivalentes a 'completado'.
     *
     * @return bool True si el estado es completed, done, approved, triggered, accepted o finished
     */
    public function isCompleted(): bool
    {
        return in_array($this->status_value, ['completed', 'done', 'approved', 'triggered', 'accepted', 'finished']);
    }

    /**
     * Determina si la actividad está pendiente.
     * Compara status_value contra estados semánticamente equivalentes a 'pendiente'.
     *
     * @return bool True si el estado es pending, draft, scheduled, proposed o todo
     */
    public function isPending(): bool
    {
        return in_array($this->status_value, ['pending', 'draft', 'scheduled', 'proposed', 'todo']);
    }

    // ─── Compatibilidad con partial timer (Task compat layer) ─────────────────

    /**
     * Relación con TimeLog apuntando al task_id (columna polivalente).
     * Permite que Activity use el mismo sistema de tracking de tiempo que Task.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\TimeLog, $this>
     */
    public function timeLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\TimeLog::class, 'task_id', 'id');
    }

    /**
     * Actualiza la prioridad de forma automática basándose en el tiempo restante.
     *
     * Reglas:
     * - Si no tiene auto_priority habilitado, due_date, o ya está completada: no hace nada
     * - Si la fecha de vencimiento ya pasó: priority = 'critical'
     * - Si queda menos del 10% del tiempo: 'critical'
     * - Si queda menos del 25%: 'high'
     * - Si queda menos del 50%: 'medium'
     *
     * @return void
     */
    public function updateAutoPriority()
    {
        if (!$this->auto_priority || !$this->due_date || $this->status === 'completed') {
            return;
        }

        $start = $this->scheduled_date ?: $this->created_at;
        $now = now();
        $due = $this->due_date;

        if ($now->gt($due)) {
            $this->priority = 'critical';
            $this->save();
            return;
        }

        $totalDuration = $start->diffInSeconds($due);
        if ($totalDuration <= 0) return;

        $remainingTime = $now->diffInSeconds($due, false);
        $percentageRemaining = ($remainingTime / $totalDuration) * 100;

        $newPriority = $this->priority;

        if ($percentageRemaining < 10) {
            $newPriority = 'critical';
        } elseif ($percentageRemaining < 25) {
            $newPriority = 'high';
        } elseif ($percentageRemaining < 50) {
            $newPriority = 'medium';
        }

        if ($newPriority !== $this->priority) {
            $this->priority = $newPriority;
            $this->save();
        }
    }

    /**
     * Determina si la actividad aparece en el Kanban.
     * Solo los tipos en KANBAN_TYPES con kanban_column_id asignado aparecen.
     *
     * @return bool True si el tipo está en KANBAN_TYPES y tiene kanban_column_id
     */
    public function isInKanban(): bool
    {
        return in_array($this->type, self::KANBAN_TYPES) && $this->kanban_column_id !== null;
    }

    /**
     * Determina si la actividad aparece en la Matriz Eisenhower.
     * Solo los tipos en MATRIX_TYPES con matrix_order asignado aparecen.
     *
     * @return bool True si el tipo está en MATRIX_TYPES y tiene matrix_order
     */
    public function isInMatrix(): bool
    {
        return in_array($this->type, self::MATRIX_TYPES) && $this->matrix_order !== null;
    }

    /**
     * Determina si la actividad aparece en el diagrama Gantt.
     * Solo los tipos en GANTT_TYPES con due_date asignado aparecen.
     *
     * @return bool True si el tipo está en GANTT_TYPES y tiene due_date
     */
    public function isInGantt(): bool
    {
        return in_array($this->type, self::GANTT_TYPES) && $this->due_date !== null;
    }

    /**
     * Devuelve la clase CSS para el diagrama Gantt basada en el tipo de actividad.
     *
     * @return string Clase CSS (gantt-task, gantt-meeting, gantt-document, etc.)
     */
    public function getGanttColorClass(): string
    {
        return match($this->type) {
            'task'     => 'gantt-task',
            'meeting'  => 'gantt-meeting',
            'document' => 'gantt-document',
            'reminder' => 'gantt-reminder',
            'agreement' => 'gantt-decision',
            default    => 'gantt-default',
        };
    }

    /**
     * Sincroniza automáticamente la columna Kanban de la actividad según su progreso.
     *
     * Mapeo:
     * - 100% → columna tipo 'done'
     * - 0% → columna tipo 'todo'
     * - Intermedio → columna tipo 'in_progress' o 'custom'
     *
     * Asigna la primera columna por defecto del equipo que coincida con el tipo esperado.
     *
     * @return void
     */
    public function syncKanbanColumn(): void
    {
        $team = $this->team;
        if (!$team && $this->team_id) {
            $team = Team::find($this->team_id);
        }
        if (!$team) return;

        $currentProgress = (int)$this->progress;
        $expectedTypes = [];
        if ($currentProgress === 100) {
            $expectedTypes = ['done'];
        } elseif ($currentProgress === 0) {
            $expectedTypes = ['todo'];
        } else {
            $expectedTypes = ['in_progress', 'custom'];
        }

        $currentColumn = \App\Models\KanbanColumn::find($this->kanban_column_id);

        if (!$currentColumn || !in_array($currentColumn->type, $expectedTypes)) {
            $typeToAssign = $currentProgress === 100 ? 'done' : ($currentProgress === 0 ? 'todo' : 'in_progress');
            
            $defaultColumn = $team->kanbanColumns()
                ->where('type', $typeToAssign)
                ->orderBy('order_index')
                ->first();

            if ($defaultColumn && $this->kanban_column_id !== $defaultColumn->id) {
                $this->kanban_column_id = $defaultColumn->id;
                $this->saveQuietly();
            }
        }
    }

    /**
     * Notifica al creador y coordinadores sobre un evento de actividad.
     *
     * Recipients:
     * 1. Creador (si no es el usuario autenticado)
     * 2. Coordinadores filtrados por visibilidad:
     *    - 'public': todos los coordinadores del equipo
     *    - 'semi-private': solo creador y asignados
     *    - 'private' o NULL: solo creador
     *
     * @param \Illuminate\Notifications\Notification $notification Instancia de notificación a enviar
     * @return void
     */
    public function notifyCreatorAndCoordinators($notification)
    {
        $recipients = collect();

        // 1. Add Creator
        if ($this->creator && $this->creator->id !== auth()->id()) {
            $recipients->push($this->creator);
        }

        // 2. Add Coordinators (filtered by visibility/involvement)
        $coordinators = $this->team->coordinators()
            ->where('users.id', '!=', auth()->id())
            ->get();
            
        $filteredCoordinators = $coordinators->filter(function ($coordinator) {
            if ($this->visibility === 'public') {
                return true;
            }
            // 'semi-private': solo creador + asignados
            if (in_array($this->visibility, ['semi-private', 'semiprivate'])) {
                if ($this->created_by_id === $coordinator->id) return true;
                if ($this->assignedTo->contains('id', $coordinator->id)) return true;
                if ($this->assignedGroups->filter(fn($g) => $g->users->contains('id', $coordinator->id))->isNotEmpty()) return true;
                return false;
            }
            // 'private' o NULL: solo creador
            return $this->created_by_id === $coordinator->id;
        });
        
        $recipients = $recipients->merge($filteredCoordinators)->unique('id');

        foreach ($recipients as $recipient) {
            $recipient->notify($notification);
        }
    }

    /**
     * Notifica a los coordinadores si la actividad fue completada y cumple criterios específicos.
     *
     * Reglas de notificación al completar:
     * - 'private' o NULL: solo al creador (si no es quien completó)
     * - 'semi-private': creador + asignado (si no son quien completó)
     * - 'public' + template/coordinator-created: a todos los coordinadores (excluyendo al actor)
     * - 'public' normal: solo al creador (si no es quien completó)
     *
     * Envía TaskCompletedNotification a cada destinatario.
     *
     * @return void
     */
    public function notifyCoordinatorsIfCompleted()
    {
        if ($this->status_value !== 'completed') return;

        $actor = auth()->user() ?? $this->assignedUser ?? $this->creator;
        $actorId = $actor ? $actor->id : null;
        
        $recipients = collect();

        if ($this->visibility === 'private' || is_null($this->visibility)) {
            // Privada: solo creador
            if ($this->creator && $this->creator->id !== $actorId) {
                $recipients->push($this->creator);
            }
        } elseif (in_array($this->visibility, ['semi-private', 'semiprivate'])) {
            // Semi-privada: creador + asignados
            if ($this->creator && $this->creator->id !== $actorId) {
                $recipients->push($this->creator);
            }
            if ($this->assignedUser && $this->assignedUser->id !== $actorId) {
                $recipients->push($this->assignedUser);
            }
        } else {
            // Pública: coordinadores del equipo
            if ($this->is_template || ($this->creator && $this->team->isCoordinator($this->creator))) {
                $coordinators = $this->team->coordinators()
                    ->when($actorId, fn($q) => $q->where('users.id', '!=', $actorId))
                    ->get();
                $recipients = $recipients->merge($coordinators);
            } else {
                if ($this->creator && $this->creator->id !== $actorId) {
                    $recipients->push($this->creator);
                }
            }
        }

        $recipients = $recipients->unique('id');

        foreach ($recipients as $recipient) {
            $recipient->notify(new \App\Notifications\TaskCompletedNotification($this, $actor));
        }
    }

}
