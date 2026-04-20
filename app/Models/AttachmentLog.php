<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttachmentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'attachment_id',
        'user_id',
        'action',
        'metadata',
        'ip_address',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the attachment associated with the log.
     */
    public function attachment()
    {
        return $this->belongsTo(TaskAttachment::class, 'attachment_id');
    }

    /**
     * Get the user who performed the action.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
