<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Models;

use App\Models\User;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo para carpetas de la biblioteca documental del equipo.
 *
 * @property int $id
 * @property string $uuid
 * @property int $team_id
 * @property int|null $created_by_id
 * @property int|null $parent_id
 * @property string $name
 * @property string|null $description
 * @property string $visibility
 * @property string $color
 * @property string $icon
 * @property int $order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read \App\Models\Team $team
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\TeamFolder|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TeamFolder> $children
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Activity> $documents
 */
class TeamFolder extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    /**
     * Paleta de colores soportados para carpetas con sus clases Tailwind.
     */
    public const COLORS = [
        'violet'  => ['bg' => 'bg-violet-50 dark:bg-violet-900/30', 'text' => 'text-violet-600 dark:text-violet-400', 'border' => 'border-violet-200 dark:border-violet-800'],
        'blue'    => ['bg' => 'bg-blue-50 dark:bg-blue-900/30',     'text' => 'text-blue-600 dark:text-blue-400',     'border' => 'border-blue-200 dark:border-blue-800'],
        'emerald' => ['bg' => 'bg-emerald-50 dark:bg-emerald-900/30','text' => 'text-emerald-600 dark:text-emerald-400','border' => 'border-emerald-200 dark:border-emerald-800'],
        'amber'   => ['bg' => 'bg-amber-50 dark:bg-amber-900/30',   'text' => 'text-amber-600 dark:text-amber-400',   'border' => 'border-amber-200 dark:border-amber-800'],
        'rose'    => ['bg' => 'bg-rose-50 dark:bg-rose-900/30',     'text' => 'text-rose-600 dark:text-rose-400',     'border' => 'border-rose-200 dark:border-rose-800'],
        'indigo'  => ['bg' => 'bg-indigo-50 dark:bg-indigo-900/30', 'text' => 'text-indigo-600 dark:text-indigo-400', 'border' => 'border-indigo-200 dark:border-indigo-800'],
        'cyan'    => ['bg' => 'bg-cyan-50 dark:bg-cyan-900/30',     'text' => 'text-cyan-600 dark:text-cyan-400',     'border' => 'border-cyan-200 dark:border-cyan-800'],
        'gray'    => ['bg' => 'bg-gray-100 dark:bg-gray-800/50',    'text' => 'text-gray-600 dark:text-gray-400',    'border' => 'border-gray-200 dark:border-gray-700'],
    ];

    protected $table = 'team_folders';

    protected $fillable = [
        'uuid',
        'team_id',
        'created_by_id',
        'parent_id',
        'name',
        'description',
        'visibility',
        'color',
        'icon',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    // ─── Relaciones ──────────────────────────────────────────────────────────

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('order')
            ->orderBy('name');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Activity::class, 'folder_id')
            ->where('type', 'document');
    }

    public function links(): HasMany
    {
        return $this->hasMany(Activity::class, 'folder_id')
            ->where('type', 'link');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'folder_id');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeInTeam(Builder $query, int $teamId): Builder
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope para filtrar carpetas visibles para un usuario.
     * Las carpetas públicas son visibles para todo el equipo.
     * Las carpetas privadas solo son visibles para su creador y los managers del equipo.
     */
    public function scopeVisibleTo(Builder $query, ?User $user, bool $isManager = false): Builder
    {
        if (!$user) {
            return $query->where('visibility', 'public');
        }

        return $query->where(function (Builder $q) use ($user, $isManager) {
            $q->where('visibility', 'public')
              ->orWhere('created_by_id', $user->id);

            if ($isManager) {
                $q->orWhere('visibility', 'private');
            }
        });
    }

    // ─── Métodos de jerarquía y utilidad ─────────────────────────────────────

    /**
     * Determina si la carpeta es pública para todo el equipo.
     */
    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    /**
     * Determina si la carpeta es privada.
     */
    public function isPrivate(): bool
    {
        return $this->visibility === 'private';
    }

    /**
     * Determina si la carpeta es visible para el usuario dado.
     */
    public function isVisibleTo(?User $user, bool $isManager = false): bool
    {
        if ($this->visibility === 'public') {
            return true;
        }

        if (!$user) {
            return false;
        }

        if ($this->created_by_id === $user->id) {
            return true;
        }

        return $isManager;
    }

    /**
     * Construye la lista de migas de pan (breadcrumbs) desde la raíz hasta esta carpeta.
     *
     * @return array<int, array{id: int, uuid: string, name: string, color: string, visibility: string}>
     */
    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [];
        $current = $this;

        while ($current) {
            array_unshift($breadcrumbs, [
                'id'         => $current->id,
                'uuid'       => $current->uuid,
                'name'       => $current->name,
                'color'      => $current->color,
                'visibility' => $current->visibility,
            ]);
            $current = $current->parent;
        }

        return $breadcrumbs;
    }

    /**
     * Obtiene recursivamente todos los IDs de subcarpetas descendientes.
     *
     * @return array<int>
     */
    public function getAllDescendantIds(): array
    {
        $ids = [];
        foreach ($this->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $child->getAllDescendantIds());
        }
        return $ids;
    }

    /**
     * Valida si esta carpeta puede moverse a una carpeta destino sin crear ciclos.
     */
    public function canMoveTo(?self $targetFolder): bool
    {
        if ($targetFolder === null) {
            return true; // Mover a la raíz siempre es válido
        }

        if ($targetFolder->id === $this->id) {
            return false; // No se puede mover dentro de sí misma
        }

        if ($targetFolder->team_id !== $this->team_id) {
            return false; // Mismo equipo requerido
        }

        // No puede ser hija de ninguna de sus descendientes
        return !in_array($targetFolder->id, $this->getAllDescendantIds(), true);
    }

    /**
     * Retorna las clases de color correspondientes.
     */
    public function getColorStyles(): array
    {
        return self::COLORS[$this->color] ?? self::COLORS['violet'];
    }
}

