<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatGroup extends Model
{
    protected $fillable = ['name', 'created_by'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_group_user')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'chat_group_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getAvatarAttribute()
    {
        return 'https://ui-avatars.com/api/?name=Grupo&color=10b981&background=ecfdf5';
    }
}
