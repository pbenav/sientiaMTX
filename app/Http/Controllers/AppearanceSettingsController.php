<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;

class AppearanceSettingsController extends Controller
{
    /**
     * Show the appearance settings form.
     */
    public function edit()
    {
        return view('settings.appearance', [
            'markdown' => [
                'h1_size' => Setting::get('markdown_h1_size', '1.875rem'),
                'h1_weight' => Setting::get('markdown_h1_weight', '800'),
                'h2_size' => Setting::get('markdown_h2_size', '1.5rem'),
                'h2_weight' => Setting::get('markdown_h2_weight', '700'),
                'h3_size' => Setting::get('markdown_h3_size', '1.25rem'),
                'h3_weight' => Setting::get('markdown_h3_weight', '600'),
                'text_size' => Setting::get('markdown_text_size', '1rem'),
                'text_min_size' => Setting::get('markdown_text_min_size', '0.6875rem'),
                'accent_color' => Setting::get('markdown_accent_color', '#4f46e5'),
                'bullet_color' => Setting::get('markdown_bullet_color', '#4f46e5'),
                'bq_color' => Setting::get('markdown_bq_color', '#4f46e5'),
                'bq_width' => Setting::get('markdown_bq_width', '4px'),
            ]
        ]);
    }

    /**
     * Update the appearance settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'markdown_h1_size' => 'required|string|max:10',
            'markdown_h1_weight' => 'required|string|max:10',
            'markdown_h2_size' => 'required|string|max:10',
            'markdown_h2_weight' => 'required|string|max:10',
            'markdown_h3_size' => 'required|string|max:10',
            'markdown_h3_weight' => 'required|string|max:10',
            'markdown_text_size' => 'required|string|max:10',
            'markdown_text_min_size' => 'required|string|max:10',
            'markdown_accent_color' => 'required|string|max:10',
            'markdown_bullet_color' => 'required|string|max:10',
            'markdown_bq_color' => 'required|string|max:10',
            'markdown_bq_width' => 'required|string|max:10',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value);
        }

        return back()->with('success', __('¡Ajustes de apariencia actualizados correctamente!'));
    }
}
