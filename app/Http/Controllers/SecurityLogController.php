<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SecurityLog;

class SecurityLogController extends Controller
{
    /**
     * Show the security logs list.
     */
    public function index(Request $request)
    {
        $query = SecurityLog::with('user');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('event', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(30)->withQueryString();

        return view('settings.security', compact('logs', 'search'));
    }
}
