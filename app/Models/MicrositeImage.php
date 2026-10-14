<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Imagen adjunta a un micrositio para su uso en el contenido HTML/CSS.
 *
 * @property int $id
 * @property int $team_id
 * @property int $microsite_id
 * @property int $user_id
 * @property string|null $title
 * @property string $file_name
 * @property string $file_path
 * @property string $disk
 * @property string|null $mime_type
 * @property int $file_size
 * @property string $url
 * @property string $formatted_size
 * @property string $html_snippet
 *
 * @property-read \App\Models\Microsite $microsite
 * @property-read \App\Models\Team $team
 * @property-read \App\Models\User $user
 */
class MicrositeImage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'team_id',
        'microsite_id',
        'user_id',
        'title',
        'file_name',
        'file_path',
        'disk',
        'mime_type',
        'file_size',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    protected $appends = [
        'url',
        'formatted_size',
        'html_snippet',
    ];

    protected static function booted()
    {
        static::deleting(function ($image) {
            // Eliminar el archivo físico al borrar el registro
            if ($image->file_path && Storage::disk($image->disk ?? 'public')->exists($image->file_path)) {
                Storage::disk($image->disk ?? 'public')->delete($image->file_path);
            }
        });
    }

    public function microsite(): BelongsTo
    {
        return $this->belongsTo(Microsite::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * URL pública de la imagen para su inserción en el micrositio.
     */
    public function getUrlAttribute(): string
    {
        if (!$this->file_path) {
            return '';
        }

        return Storage::disk($this->disk ?? 'public')->url($this->file_path);
    }

    /**
     * Tamaño formateado legible (KB, MB).
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = (int) $this->file_size;
        if ($bytes <= 0) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = (int) floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 1) . ' ' . ($units[$i] ?? 'B');
    }

    /**
     * Snippet HTML recomendado para insertar en el código del micrositio.
     */
    public function getHtmlSnippetAttribute(): string
    {
        $alt = htmlspecialchars($this->title ?: pathinfo($this->file_name, PATHINFO_FILENAME), ENT_QUOTES, 'UTF-8');
        return '<img src="' . $this->url . '" alt="' . $alt . '" class="ms-card-img rounded-2xl shadow-md my-4 max-w-full h-auto">';
    }
}
