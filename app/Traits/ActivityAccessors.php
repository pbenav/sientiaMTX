<?php

namespace App\Traits;



trait ActivityAccessors
{
    // ─── Accessors de tipo/metadatos ──────────────────────────────────────────
    public function getIsAutoprogrammableAttribute(): bool
    {
        return data_get($this->metadata, 'is_autoprogrammable', false);
    }

    public function getPrivacyLevelAttribute(): string
    {
        return $this->visibility ?? 'private';
    }

    public function getAvgQualityScoreAttribute(): float
    {
        return data_get($this->metadata, 'avg_quality_score', 0);
    }

    // ─── Accessors de asignación ──────────────────────────────────────────────
    /**
     * Devuelve el primer usuario asignado individualmente (compat. con vista de tareas).
     * Si se ha cargado eager loading de assignedTo, lo usa sin hacer nueva query.
     */
    public function getAssignedUserAttribute(): ?\App\Models\User
    {
        if ($this->relationLoaded('assignedTo')) {
            return $this->assignedTo->first();
        }
        return $this->assignedTo()->first();
    }

    public function getAssignedUserIdAttribute(): ?int
    {
        if ($this->relationLoaded('assignedTo')) {
            return $this->assignedTo->first()?->id;
        }
        return $this->assignedTo()->value('users.id');
    }

    // ─── Accessors de estado/progreso ─────────────────────────────────────────
    public function getStatusValueAttribute(): ?string
    {
        return $this->status['value'] ?? null;
    }

    /**
     * Compat con Task: devuelve el % de progreso.
     * Para Activity usa progress_percentage directamente.
     */
    public function getProgressAttribute(): int
    {
        if (in_array($this->status_value, ['completed', 'done', 'approved', 'triggered', 'accepted', 'finished'])) return 100;
        return (int) ($this->progress_percentage ?? 0);
    }

    // ─── Accessors de urgencia (Matriz Eisenhower) ────────────────────────────
    public function getUrgencyAttribute(): string
    {
        $meta = $this->metadata ?? [];
        return $meta['urgency'] ?? 'medium';
    }

    public function setUrgencyAttribute(string $value): void
    {
        $meta = $this->metadata ?? [];
        $meta['urgency'] = $value;
        $this->metadata = $meta;
    }

    // ─── Accessores de especialidad y servicio (Task compat layer) ────────────
    public function getSkillIdAttribute(): ?int
    {
        return data_get($this->metadata, 'skill_id') ?? ($this->relationLoaded('skills') ? $this->skills->first()?->id : null);
    }

    public function getServiceIdAttribute(): ?int
    {
        return data_get($this->metadata, 'service_id');
    }

    // ─── Accessores UI helpers ────────────────────────────────────────────────
    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'task'     => '<svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
            'document' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>',
            'note'     => '<svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>',
            'link'     => '<svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>',
            'agreement' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" /></svg>',
            'meeting'  => '<svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>',
            'reminder' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>',
            default    => '<svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6z" /></svg>',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'task'      => trans('Tarea'),
            'document'  => trans('Documento'),
            'note'      => trans('Nota'),
            'link'      => trans('Enlace'),
            'agreement' => trans('Acuerdo'),
            'meeting'   => trans('Reunión'),
            'reminder'  => trans('Recordatorio'),
            default     => trans('Actividad'),
        };
    }

    public function getTypeBadgeColorAttribute(): string
    {
        return match($this->type) {
            'task'      => 'blue',
            'document'  => 'orange',
            'note'      => 'yellow',
            'link'      => 'purple',
            'agreement' => 'red',
            'meeting'   => 'green',
            'reminder'  => 'pink',
            default     => 'gray',
        };
    }
}
