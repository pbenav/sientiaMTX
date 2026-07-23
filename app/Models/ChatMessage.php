<?php

namespace App\Models;

use App\Traits\HasDemoMasking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Mensaje de chat con archivos adjuntos.
 *
 * Representa un mensaje en un chat de equipo, con soporte para
 * mensajes directos (sender/receiver) y grupales (chat_group_id),
 * respuestas a mensajes previos, archivos adjuntos y enmascaramiento
 * de datos sensibles en modo demo.
 *
 * Campos clave:
 * - sender_id: ID del usuario remitente
 * - receiver_id: ID del usuario destinatario (null para chats grupales)
 * - chat_group_id: ID del grupo de chat (null para mensajes directos)
 * - message: Contenido del mensaje
 * - is_read: Si el mensaje ha sido leído
 * - call_room: Nombre de la sala de videollamada asociada
 * - file_name: Nombre del archivo adjunto
 * - file_path: Ruta del archivo adjunto
 * - file_type: Tipo MIME del archivo adjunto
 * - file_size: Tamaño del archivo adjunto en bytes
 * - storage_provider: Proveedor de almacenamiento (local, google_drive, etc.)
 * - web_view_link: URL de vista web del archivo
 * - parent_id: ID del mensaje al que se responde (null si es mensaje principal)
 *
 * @property-read int $sender_id
 * @property-read int|null $receiver_id
 * @property-read int|null $chat_group_id
 * @property-read string|null $message
 * @property-read bool $is_read
 * @property-read string|null $call_room
 * @property-read string|null $file_name
 * @property-read string|null $file_path
 * @property-read string|null $file_type
 * @property-read int|null $file_size
 * @property-read string|null $storage_provider
 * @property-read string|null $web_view_link
 * @property-read int|null $parent_id
 * @property-read string|null $file_url
 *
 * @property-read \App\Models\User $sender
 * @property-read \App\Models\User|null $receiver
 * @property-read \App\Models\ChatGroup|null $group
 * @property-read \App\Models\ChatMessage|null $parent
 *
 * @mixin Builder
 */
class ChatMessage extends Model
{
    use HasDemoMasking;

    protected array $demoSensitiveAttributes = [
        'message'   => 'text',
        'file_name' => 'text',
    ];
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'chat_group_id',
        'message',
        'is_read',
        'call_room',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'storage_provider',
        'web_view_link',
        'parent_id',
    ];

    protected $appends = ['file_url'];

    /**
     * Atributo accesible: URL pública del archivo adjunto.
     *
     * @return string|null URL del archivo o null si no hay archivo
     */
    public function getFileUrlAttribute(): ?string
    {
        if (!$this->file_path) return null;
        return \Illuminate\Support\Facades\Storage::url($this->file_path);
    }

    /**
     * Relación de pertenencia al usuario remitente del mensaje.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Relación de pertenencia al usuario destinatario del mensaje.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Relación de pertenencia al grupo de chat del mensaje.
     *
     * @return BelongsTo<\App\Models\ChatGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(ChatGroup::class, 'chat_group_id');
    }

    /**
     * Relación de pertenencia al mensaje padre (respuesta).
     *
     * @return BelongsTo<\App\Models\ChatMessage, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'parent_id');
    }
}
