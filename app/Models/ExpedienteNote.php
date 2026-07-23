<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Nota de expediente con privacidad.
 *
 * Permite adjuntar notas a un expediente, marcándolas como
 * privadas o públicas, y registra el usuario que creó la nota.
 *
 * Campos clave:
 * - expediente_id: ID del expediente al que pertenece la nota
 * - user_id: ID del usuario que creó la nota
 * - content: Contenido de la nota
 * - is_private: Si la nota es privada (solo visible por el creador)
 *
 * @property-read int $expediente_id
 * @property-read int $user_id
 * @property-read string $content
 * @property-read bool $is_private
 *
 * @property-read \App\Models\Expediente $expediente
 * @property-read \App\Models\User $user
 *
 * @mixin Builder
 */
class ExpedienteNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'expediente_id',
        'user_id',
        'content',
        'is_private',
    ];

    protected $casts = [
        'is_private' => 'boolean',
    ];

    /**
     * Relación de pertenencia al expediente asociado.
     *
     * @return BelongsTo<\App\Models\Expediente, $this>
     */
    public function expediente()
    {
        return $this->belongsTo(Expediente::class);
    }

    /**
     * Relación de pertenencia al usuario que creó la nota.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
