<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ThemeController extends Controller
{
    /**
     * Update the user's theme preference.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'theme' => 'required|in:light,dark,system',
        ]);

        $user = auth()->user();
        $user->theme = $validated['theme'];
        $user->save();

        return response()->json(['success' => true]);
    }
}
