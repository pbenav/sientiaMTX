<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * DemoMode Facade
 *
 * Provides static access to DemoModeService from anywhere in the app,
 * including Blade views and controllers.
 *
 * Usage:
 *   DemoMode::isActive()
 *   DemoMode::mask($user->name, 'name')
 *   DemoMode::mask($user->email, 'email')
 *   DemoMode::blurClass()
 *
 * @method static bool   isActive()
 * @method static string mask(?string $value, string $type = 'text')
 * @method static string blurClass()
 * @method static string blurStyle()
 *
 * @see \App\Services\DemoModeService
 */
class DemoMode extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\DemoModeService::class;
    }
}
