<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MotivationalQuote extends Model
{
    protected $fillable = ['text', 'author', 'type'];
}
