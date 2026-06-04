<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class ProfileSessionController extends Controller
{
    /**
     * Log out a specific session remotely.
     */
    public function destroy(Request $request, $sessionId): RedirectResponse
    {
        DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->delete();

        return Redirect::route('profile.edit', ['tab' => $request->input('tab', 'security')])->with('status', 'session-logged-out');
    }
}
