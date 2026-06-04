<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use App\Models\Invitation;

class UserInvitationController extends Controller
{
    /**
     * Generar un nuevo pase VIP de invitación.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->invitations_left <= 0) {
            return Redirect::route('profile.edit', ['tab' => 'invitations_vip'])->with('error', 'No te quedan invitaciones disponibles.');
        }

        $validated = $request->validate([
            'team_id' => 'nullable|exists:teams,id',
        ]);

        Invitation::create([
            'user_id' => $user->id,
            'team_id' => $validated['team_id'] ?? null,
            'code' => 'VIP-' . strtoupper(Str::random(8)),
        ]);

        return Redirect::route('profile.edit', ['tab' => 'invitations_vip'])->with('status', 'invitation-generated');
    }

    /**
     * Eliminar un pase VIP de invitación generado por el usuario (solo si no ha sido usado).
     */
    public function destroy(Request $request, Invitation $invitation): RedirectResponse
    {
        // Solo puede borrar sus propias invitaciones y solo si no han sido usadas
        if ($invitation->user_id !== $request->user()->id || $invitation->used_at) {
            return Redirect::route('profile.edit', ['tab' => 'invitations_vip'])->with('error', 'No puedes eliminar esta invitación.');
        }

        $invitation->delete();

        return Redirect::route('profile.edit', ['tab' => 'invitations_vip'])->with('status', 'invitation-deleted');
    }
}
