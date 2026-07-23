<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Http\Controllers;

use App\Models\Kudo;
use App\Models\Team;
use App\Models\User;
use App\Models\GamificationLog;
use Illuminate\Http\Request;

/**
 * Controlador para gestionar los Kudos (reconocimientos entre compañeros) dentro de un equipo.
 *
 * Permite a los usuarios enviar Kudos con tipos personalizables, mensajes y vinculación a tareas.
 * Al enviar un Kudo se otorgan puntos de experiencia y resiliencia, se registra un log de gamificación
 * y se envía una notificación al receptor.
 *
 * Ruta asociada: POST /teams/{team}/kudos
 */
class KudoController extends Controller
{
    /**
     * Registra un nuevo Kudo enviado por el usuario autenticado a otro usuario del equipo.
     *
     * Otorga puntos de experiencia y resiliencia al receptor, crea un registro de gamificación
     * y envía una notificación al usuario recibido.
     *
     * @param Request $request
     * @param Team $team
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, Team $team)
    {
        $validated = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'type' => 'required|array|min:1',
            'type.*' => 'string',
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
            'type' => implode(', ', $validated['type']),
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

        // Notify Receiver
        $receiver->notify(new \App\Notifications\KudoReceivedNotification($kudo, auth()->user()));

        return back()->with('success', '¡Kudo enviado con éxito! Has alegrado el día de un compañero.');
    }
}
