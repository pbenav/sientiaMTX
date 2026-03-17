<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LayoutController extends Controller
{
    /**
     * Update the user's layout preference.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'layout' => 'required|in:horizontal,vertical',
        ]);

        $user = auth()->user();
        $user->layout = $validated['layout'];
        $user->save();

        return response()->json(['success' => true]);
    }
}
