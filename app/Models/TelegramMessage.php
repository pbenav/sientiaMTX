<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use App\Traits\HasDemoMasking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Mensaje de Telegram con rutas de archivos multimedia.
 *
 * Representa un mensaje enviado a través de Telegram, con datos
 * del autor, contenido, archivos multimedia (fotos, audios, stickers)
 * y URLs públicas para acceder a ellos.
 *
 * Campos clave:
 * - team_id: ID del equipo al que pertenece el mensaje
 * - user_id: ID del usuario que envió el mensaje
 * - author_name: Nombre del autor del mensaje
 * - text: Texto del mensaje
 * - photo_path: Ruta de la foto adjunta
 * - telegram_message_id: ID del mensaje en Telegram
 * - is_from_web: Si el mensaje proviene de la web
 * - is_deleted_on_telegram: Si el mensaje fue eliminado en Telegram
 * - voice_path: Ruta del audio adjunto
 * - voice_duration: Duración del audio en segundos
 * - sticker_path: Ruta del sticker adjunto
 * - file_type: Tipo de archivo adjunto
 * - file_size: Tamaño del archivo en bytes
 * - reply_to_message_id: ID del mensaje al que se responde
 * - reply_to_text: Texto del mensaje al que se responde
 *
 * @property-read int $team_id
 * @property-read int $user_id
 * @property-read string|null $author_name
 * @property-read string|null $text
 * @property-read string|null $photo_path
 * @property-read string $telegram_message_id
 * @property-read bool $is_from_web
 * @property-read bool $is_deleted_on_telegram
 * @property-read string|null $voice_path
 * @property-read int|null $voice_duration
 * @property-read string|null $sticker_path
 * @property-read string|null $file_type
 * @property-read int|null $file_size
 * @property-read string|null $reply_to_message_id
 * @property-read string|null $reply_to_text
 *
 * @property-read string|null $photo_url
 * @property-read string|null $voice_url
 * @property-read string|null $sticker_url
 *
 * @property-read \App\Models\Team $team
 * @property-read \App\Models\User $user
 *
 * @mixin Builder
 */
class TelegramMessage extends Model
{
    use HasDemoMasking;

    protected array $demoSensitiveAttributes = [
        'author_name'     => 'name',
        'text'            => 'text',
        'reply_to_text'   => 'text',
    ];
    protected $fillable = [
        'team_id',
        'user_id',
        'author_name',
        'text',
        'photo_path',
        'telegram_message_id',
        'is_from_web',
        'is_deleted_on_telegram',
        'voice_path',
        'voice_duration',
        'sticker_path',
        'file_type',
        'file_size',
        'reply_to_message_id',
        'reply_to_text',
    ];

    /**
     * Relación de pertenencia al equipo del mensaje.
     *
     * @return BelongsTo<\App\Models\Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Relación de pertenencia al usuario que envió el mensaje.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Atributo accesible: URL pública de la foto adjunta.
     *
     * @return string|null
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo_path) return null;
        if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($this->photo_path)) return null;
        return asset('storage/' . $this->photo_path);
    }

    /**
     * Atributo accesible: URL pública del audio adjunto.
     *
     * @return string|null
     */
    public function getVoiceUrlAttribute(): ?string
    {
        if (!$this->voice_path) return null;
        if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($this->voice_path)) return null;
        return asset('storage/' . $this->voice_path);
    }

    /**
     * Atributo accesible: URL pública del sticker adjunto.
     *
     * @return string|null
     */
    public function getStickerUrlAttribute(): ?string
    {
        if (!$this->sticker_path) return null;
        if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($this->sticker_path)) return null;
        return asset('storage/' . $this->sticker_path);
    }
}
