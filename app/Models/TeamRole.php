<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamRole extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('active_roles', function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->where('name', '!=', 'moderator');
        });
    }
}
