<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Grupo de chat con nombres dinámicos.
 *
 * Representa un grupo de chat con sus miembros y mensajes.
 * El nombre se genera dinámicamente a partir de los nombres de
 * los usuarios si no tiene un nombre personalizado o si coincide
 * con el patrón de nombre por defecto.
 *
 * Campos clave:
 * - name: Nombre del grupo
 * - created_by: ID del usuario que creó el grupo
 *
 * @property-read string $name
 * @property-read int $created_by
 * @property-read string $avatar
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChatMessage> $messages
 * @property-read \App\Models\User $creator
 *
 * @mixin Builder
 */
class ChatGroup extends Model
{
    protected $fillable = ['name', 'created_by'];

    /**
     * Relación muchos-a-muchos con los usuarios del grupo.
     *
     * @return BelongsToMany<\App\Models\User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_group_user')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    /**
     * Relación uno-a-muchos con los mensajes del grupo.
     *
     * @return HasMany<\App\Models\ChatMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'chat_group_id');
    }

    /**
     * Relación de pertenencia al usuario que creó el grupo.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Atributo accesible: URL del avatar del grupo.
     *
     * @return string URL del avatar generado por ui-avatars.com
     */
    public function getAvatarAttribute()
    {
        return 'https://ui-avatars.com/api/?name=Grupo&color=10b981&background=ecfdf5';
    }

    /**
     * Accesor para obtener el nombre dinámico del grupo.
     *
     * Si el nombre es nulo o coincide con el patrón "Grupo de N miembros",
     * genera un nombre dinámico con los nombres de los usuarios del grupo.
     * El usuario actual aparece como "Tú" y se coloca primero.
     *
     * @param string $value Valor original del nombre
     * @return string Nombre dinámico o original
     */
    public function getNameAttribute($value): string
    {
        if (!$value || preg_match('/^Grupo de \d+ miembros$/i', $value)) {
            $currentUserId = auth()->id();
            $users = $this->users;
            if ($users->isNotEmpty()) {
                $names = $users->map(function($u) use ($currentUserId) {
                    $firstName = explode(' ', trim($u->name))[0];
                    return ($currentUserId && $u->id === $currentUserId) ? 'Tú' : $firstName;
                });

                if ($currentUserId) {
                    $me = $names->filter(fn($n) => $n === 'Tú');
                    $others = $names->filter(fn($n) => $n !== 'Tú');
                    $names = $me->merge($others);
                }

                return $names->implode(', ');
            }
        }
        return $value;
    }
}
