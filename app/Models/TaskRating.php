<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Calificación de tarea por usuario.
 *
 * Representa una calificación numérica y comentario opcional que
 * un usuario puede dar a una tarea.
 *
 * Campos clave:
 * - task_id: ID de la tarea calificada
 * - user_id: ID del usuario que califica
 * - score: Puntuación de la calificación
 * - comment: Comentario opcional sobre la calificación
 *
 * @property-read int $task_id
 * @property-read int $user_id
 * @property-read int $score
 * @property-read string|null $comment
 *
 * @property-read \App\Models\Task $task
 * @property-read \App\Models\User $user
 *
 * @mixin Builder
 */
class TaskRating extends Model
{
    protected $fillable = [
        'task_id',
        'user_id',
        'score',
        'comment'
    ];

    /**
     * Relación de pertenencia a la tarea calificada.
     *
     * @return BelongsTo<\App\Models\Task, $this>
     */
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Relación de pertenencia al usuario que califica.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
