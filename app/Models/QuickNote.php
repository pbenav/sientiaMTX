<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QuickNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content',
        'position_x',
        'position_y',
        'width',
        'height',
        'color',
        'is_pinned',
        'is_minimized',
        'attachments',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_pinned' => 'boolean',
        'is_minimized' => 'boolean',
        'position_x' => 'integer',
        'position_y' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
