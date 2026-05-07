<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quadrant extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'color_hex', 'color_description', 'order'];
}

