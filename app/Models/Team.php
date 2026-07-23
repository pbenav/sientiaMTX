<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TelegramMessage;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Traits\HasUuid;

/**
 * Equipo / Team: unidad organizativa que contiene actividades, usuarios, habilidades, servicios, etc.
 *
 * Campos de configuración:
 * - quadrant_colors: array con colores para cada cuadrante de Eisenhower
 * - settings: array con configuraciones del equipo (soft_disk_quota, etc.)
 * - disk_quota / disk_used: cuota de almacenamiento en bytes
 */
class Team extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    /**
     * Atributos asignables masivamente.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'telegram_chat_id',
        'whatsapp_chat_id',
        'created_by_id',
        'quadrant_colors',
        'settings',
        'disk_quota',
        'disk_used',
    ];

    /**
     * Casting de atributos a tipos nativos.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'quadrant_colors' => 'array',
        'settings' => 'array',
        'disk_quota' => 'integer',
        'disk_used' => 'integer',
    ];

    /**
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $tasks
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Activity> $activities
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $members
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $coordinators
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user')
            ->using(TeamUser::class)
            ->withPivot('role_id', 'google_id', 'google_token', 'google_refresh_token', 'joined_at', 'allow_appointments', 'allow_microsites')
            ->orderBy('name');
    }

    /**
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Group> $groups
     */
    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    /**
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CalendarEvent> $calendarEvents
     */
    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    /**
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TeamInvitation> $invitations
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }

    /**
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ForumThread> $forumThreads
     */
    public function forumThreads(): HasMany
    {
        return $this->hasMany(ForumThread::class);
    }

    /**
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\KanbanColumn> $kanbanColumns
     */
    public function kanbanColumns(): HasMany
    {
        return $this->hasMany(KanbanColumn::class)->orderBy('order_index');
    }

    /**
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Skill> $skills
     */
    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class);
    }

    /**
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Service> $services
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Expediente> $expedientes
     */
    public function expedientes(): HasMany
    {
        return $this->hasMany(Expediente::class);
    }

    /**
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TelegramGroupMember> $telegramGroupMembers
     */
    public function telegramGroupMembers(): HasMany
    {
        return $this->hasMany(TelegramGroupMember::class);
    }

    /**
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Survey> $surveys
     */
    public function surveys(): HasMany
    {
        return $this->hasMany(Survey::class);
    }

    /**
     * Usuario creador del equipo.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Miembros con rol de coordinador (equivalente a Admin en la jerarquía del equipo).
     */
    public function coordinators(): BelongsToMany
    {
        return $this->members()
            ->wherePivotIn('role_id', function ($query) {
                $query->select('id')->from('team_roles')->where('name', 'coordinator');
            })
            ->orderBy('name');
    }

    /**
     * Verifica si el usuario tiene rol de coordinador en este equipo.
     */
    public function isCoordinator(User $user): bool
    {
        return $this->coordinators()->where('users.id', $user->id)->exists();
    }

    /**
     * Verifica si el usuario es manager (coordinador o moderador).
     */
    public function isManager(User $user): bool
    {
        return $this->isCoordinator($user) || $this->isModerator($user);
    }

    /**
     * Verifica si el usuario tiene rol de moderador en este equipo.
     */
    public function isModerator(User $user): bool
    {
        return $this->members()
            ->where('user_id', $user->id)
            ->wherePivotIn('role_id', function ($query) {
                $query->select('id')->from('team_roles')
                    ->where('name', 'moderator');
            })
            ->exists();
    }

    /**
     * Verifica si el usuario es el owner (creador) del equipo.
     */
    public function isOwner(User $user): bool
    {
        return $this->created_by_id === $user->id;
    }
    /**
     * Configuración de colores para los cuadrantes de Eisenhower.
     *
     * Devuelve el array de colores personalizadas del equipo o los valores por defecto.
     *
     * @return array<int, array{color: string, bg: string, dot: string}>
     */
    public function getQuadrantConfig(): array
    {
        $defaults = [
            1 => ['color' => '#ef4444', 'bg' => 'bg-red-200 border-red-400 dark:bg-red-500/25 dark:border-red-500/60', 'dot' => 'bg-red-500'],
            2 => ['color' => '#3b82f6', 'bg' => 'bg-blue-200 border-blue-400 dark:bg-blue-500/25 dark:border-blue-500/60', 'dot' => 'bg-blue-500'],
            3 => ['color' => '#f59e0b', 'bg' => 'bg-amber-200 border-amber-400 dark:bg-amber-500/25 dark:border-amber-500/60', 'dot' => 'bg-amber-500'],
            4 => ['color' => '#6b7280', 'bg' => 'bg-gray-200 border-gray-400 dark:bg-gray-500/25 dark:border-gray-500/60', 'dot' => 'bg-gray-500'],
        ];

        if (empty($this->quadrant_colors)) {
            return $defaults;
        }

        $config = $defaults;
        foreach ($this->quadrant_colors as $q => $color) {
            $q = (int)$q;
            if (isset($config[$q])) {
                $config[$q]['color'] = $color;
            }
        }

        return $config;
    }

    /**
     * Convierte un color hexadecimal a cadena rgba.
     *
     * @param  string  $hex  Color hex (con o sin #)
     * @param  float  $alpha  Alpha (0-1)
     */
    public function hexToRgba($hex, $alpha = 1): string
    {
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        return "rgba($r, $g, $b, $alpha)";
    }

    /**
     * Verifica si el equipo tiene cuota de disco suficiente para un archivo.
     *
     * Sincroniza el uso actual antes de evaluar.
     *
     * @param  int  $bytes  Tamaño del archivo en bytes
     * @return bool true si hay suficiente cuota
     */
    public function hasAvailableQuota(int $bytes): bool
    {
        // Always sync from real files to avoid stale DB counters
        $this->syncDiskUsed();
        $this->refresh();

        $effectiveQuota = $this->disk_quota;
        if (isset($this->settings['soft_disk_quota']) && $this->settings['soft_disk_quota'] > 0) {
            $effectiveQuota = min($this->disk_quota, $this->settings['soft_disk_quota']);
        }

        return ($this->disk_used + $bytes) <= $effectiveQuota;
    }

    /**
     * Porcentaje de uso de disco (0-100).
     *
     * @return int Porcentaje calculado
     */
    public function getDiskUsagePercentageAttribute(): int
    {
        $effectiveQuota = $this->disk_quota;
        if (isset($this->settings['soft_disk_quota']) && $this->settings['soft_disk_quota'] > 0) {
            $effectiveQuota = min($this->disk_quota, $this->settings['soft_disk_quota']);
        }

        if ($effectiveQuota <= 0) return 0;
        return (int) min(100, round(($this->disk_used / $effectiveQuota) * 100));
    }

    /**
     * Sincroniza el campo disk_used con el tamaño real de los adjuntos.
     *
     * Calcula el tamaño total de adjuntos de tareas, foros, expedientes y mensajes de Telegram,
     * excluyendo Google Drive. Luego verifica alertas de almacenamiento.
     */
    public function syncDiskUsed(): void
    {
        // 1. Calculate task attachments size (excluding Google Drive)
        $taskIds = $this->tasks()->pluck('id');
        $taskSize = TaskAttachment::where('attachable_type', Task::class)
            ->whereIn('attachable_id', $taskIds)
            ->where('storage_provider', '!=', 'google')
            ->sum('file_size');

        // 2. Calculate forum attachments size (excluding Google Drive)
        $threadIds = $this->forumThreads()->pluck('id');
        $messageIds = ForumMessage::whereIn('forum_thread_id', $threadIds)->pluck('id');
        
        $forumSize = TaskAttachment::where('attachable_type', ForumMessage::class)
            ->whereIn('attachable_id', $messageIds)
            ->where('storage_provider', '!=', 'google')
            ->sum('file_size');

        // 3. Calculate expediente attachments size (excluding Google Drive)
        $expedienteIds = $this->expedientes()->pluck('id');
        $expedienteSize = TaskAttachment::where('attachable_type', Expediente::class)
            ->whereIn('attachable_id', $expedienteIds)
            ->where('storage_provider', '!=', 'google')
            ->sum('file_size');

        // 4. Calculate telegram media size
        $telegramSize = TelegramMessage::where('team_id', $this->id)
            ->get()
            ->sum(function($msg) {
                if ($msg->file_size > 0) return $msg->file_size;
                
                // Fallback: check physical disk for old messages
                $path = $msg->photo_path ?: ($msg->voice_path ?: $msg->sticker_path);
                if ($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                    $size = \Illuminate\Support\Facades\Storage::disk('public')->size($path);
                    // Update database to avoid re-checking disk
                    $msg->update(['file_size' => $size]);
                    return $size;
                }
                return 0;
            });

        $this->update(['disk_used' => (int)($taskSize + $forumSize + $expedienteSize + $telegramSize)]);
        
        $this->checkStorageAlerts();
    }

    /**
     * Envía notificación a coordinadores si el uso de disco supera el 90%.
     */
    public function checkStorageAlerts(): void
    {
        $percentage = $this->disk_usage_percentage;
        
        if ($percentage >= 90) {
            $coordinators = $this->coordinators;
            
            if ($coordinators->isNotEmpty()) {
                \Illuminate\Support\Facades\Notification::send(
                    $coordinators, 
                    new \App\Notifications\TeamStorageLimitReached($this, $percentage)
                );
            }
        }
    }

    /**
     * Obtiene todos los miembros del equipo con su estado de actividad actual.
     *
     * Incluye el timeLog activo del día (sin end_at y con start_at hoy).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    public function getActiveMembers()
    {
        return $this->members()
            ->with(['timeLogs' => function($q) {
                $q->whereNull('end_at')
                  ->where('start_at', '>=', now()->startOfDay());
            }])
            ->orderBy('name')
            ->get();
    }
}
