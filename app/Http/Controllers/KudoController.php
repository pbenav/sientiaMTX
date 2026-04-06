<?php

namespace App\Http\Controllers;

use App\Models\Kudo;
use App\Models\Team;
use App\Models\User;
use App\Models\GamificationLog;
use Illuminate\Http\Request;

class KudoController extends Controller
{
    public function store(Request $request, Team $team)
    {
        $validated = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'type' => 'required|string',
            'message' => 'nullable|string|max:255',
            'task_id' => 'nullable|exists:tasks,id',
        ]);

        if ($validated['receiver_id'] == auth()->id()) {
            return back()->with('error', 'No puedes enviarte un Kudo a ti mismo (aunque te lo merezcas).');
        }

        $kudo = Kudo::create([
            'from_user_id' => auth()->id(),
            'to_user_id' => $validated['receiver_id'],
            'team_id' => $team->id,
            'task_id' => $validated['task_id'] ?? null,
            'type' => $validated['type'],
            'message' => $validated['message'],
        ]);

        // Award points to receiver
        $receiver = User::find($validated['receiver_id']);
        $receiver->increment('experience_points', 10);
        $receiver->increment('resilience_points', 5);

        // Log the achievement for the receiver
        GamificationLog::create([
            'user_id' => $receiver->id,
            'team_id' => $team->id,
            'points' => 15,
            'type' => 'resilience',
            'source_type' => 'App\Models\Kudo',
            'source_id' => $kudo->id,
            'description' => "Recibido Kudo: " . $kudo->type . " de " . auth()->user()->name,
        ]);

        return back()->with('success', '¡Kudo enviado con éxito! Has alegrado el día de un compañero.');
    }
}
