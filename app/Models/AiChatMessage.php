<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiChatMessage extends Model
{
    protected $fillable = [
        'user_id',
        'team_id',
        'task_id',
        'role',
        'content',
        'file_path',
        'file_name',
        'file_type',
    ];

    protected $appends = ['file_url'];

    protected static function booted()
    {
        static::deleting(function ($message) {
            if ($message->file_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($message->file_path);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFileUrlAttribute()
    {
        return $this->file_path ? \Illuminate\Support\Facades\Storage::url($this->file_path) : null;
    }
}
