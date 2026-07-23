<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use App\Traits\HasDemoMasking;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Mensaje de WhatsApp con rutas de archivos multimedia.
 *
 * Representa un mensaje enviado a través de WhatsApp, con datos
 * del autor, contenido, archivos multimedia (fotos, audios, stickers,
 * animaciones) y estado de eliminación.
 *
 * Campos clave:
 * - team_id: ID del equipo al que pertenece el mensaje
 * - message_id: ID del mensaje en WhatsApp
 * - from_me: Si el mensaje fue enviado por el usuario actual
 * - author: Nombre del autor del mensaje
 * - text: Texto del mensaje
 * - file_type: Tipo de archivo adjunto
 * - file_mime_type: MIME type del archivo adjunto
 * - photo_path: Ruta de la foto adjunta
 * - voice_path: Ruta del audio adjunto
 * - sticker_path: Ruta del sticker adjunto
 * - animation_path: Ruta de la animación adjunta
 * - file_size: Tamaño del archivo en bytes
 * - reply_to_id: ID del mensaje al que se responde
 * - reply_to_text: Texto del mensaje al que se responde
 * - is_deleted: Si el mensaje fue eliminado
 *
 * @property-read int $team_id
 * @property-read string $message_id
 * @property-read bool $from_me
 * @property-read string|null $author
 * @property-read string|null $text
 * @property-read string|null $file_type
 * @property-read string|null $file_mime_type
 * @property-read string|null $photo_path
 * @property-read string|null $voice_path
 * @property-read string|null $sticker_path
 * @property-read string|null $animation_path
 * @property-read int|null $file_size
 * @property-read string|null $reply_to_id
 * @property-read string|null $reply_to_text
 * @property-read bool $is_deleted
 *
 * @property-read \App\Models\Team $team
 *
 * @mixin Builder
 */
class WhatsappMessage extends Model
{
    use HasFactory, HasDemoMasking;

    protected array $demoSensitiveAttributes = [
        'author'          => 'name',
        'text'            => 'text',
        'reply_to_text'   => 'text',
    ];

    protected $fillable = [
        'team_id',
        'message_id',
        'from_me',
        'author',
        'text',
        'file_type',
        'file_mime_type',
        'photo_path',
        'voice_path',
        'sticker_path',
        'animation_path',
        'file_size',
        'reply_to_id',
        'reply_to_text',
        'is_deleted'
    ];

    protected $casts = [
        'from_me' => 'boolean',
        'is_deleted' => 'boolean',
    ];

    /**
     * Relación de pertenencia al equipo del mensaje.
     *
     * @return BelongsTo<\App\Models\Team, $this>
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
