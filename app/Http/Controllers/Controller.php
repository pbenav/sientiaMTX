<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Clase base abstracta para todos los controladores de la aplicación.
 *
 * Proporciona la capacidad de autorización a través del trait AuthorizesRequests de Laravel.
 */
abstract class Controller
{
    use AuthorizesRequests;
}
