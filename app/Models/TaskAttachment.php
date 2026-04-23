<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAttachment extends Model
{
    protected $fillable = [
        'attachable_id',
        'attachable_type',
        'user_id',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'storage_provider',
        'provider_file_id',
        'web_view_link',
    ];

    public function attachable()
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs()
    {
        return $this->hasMany(AttachmentLog::class, 'attachment_id');
    }
}
