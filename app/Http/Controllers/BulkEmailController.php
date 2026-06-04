<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Team;
use App\Models\TeamRole;
use App\Jobs\SendBulkEmailJob;

class BulkEmailController extends Controller
{
    /**
     * Show the bulk email and team invitation composer.
     */
    public function create()
    {
        $teams = Team::orderBy('name')->get();
        $roles = TeamRole::all();
        return view('settings.bulk-email', compact('teams', 'roles'));
    }

    /**
     * Handle queued sending of bulk emails/invitations.
     */
    public function store(Request $request)
    {
        // Para la Fase 1 validamos campos y devolvemos un mensaje ilustrativo.
        $request->validate([
            'to' => 'required|string',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        $extractEmails = function($string) {
            if (empty($string)) return [];
            preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $string, $matches);
            return array_values(array_unique(array_map('strtolower', $matches[0])));
        };

        $toEmails = $extractEmails($request->to);
        $ccEmails = $extractEmails($request->cc);
        $bccEmails = $extractEmails($request->bcc);

        if (empty($toEmails)) {
            return back()->with('error', __('No se encontraron correos válidos en la lista de destinatarios.'));
        }

        $isInvitation = $request->boolean('is_invitation');
        $batchSize = $request->input('batch_size', 25);
        $delayMinutes = $request->input('delay_minutes', 5);

        if ($isInvitation) {
            $request->validate([
                'team_id' => 'required|exists:teams,id',
                'role_id' => 'required|exists:team_roles,id',
            ], [
                'team_id.required' => 'Debes seleccionar un equipo para enviar invitaciones.',
                'role_id.required' => 'Debes asignar un rol válido a los invitados.',
            ]);
        }

        $chunks = array_chunk($toEmails, $batchSize);
        $totalLotes = count($chunks);

        foreach ($chunks as $index => $chunk) {
            $delay = now()->addMinutes($index * $delayMinutes);
            
            SendBulkEmailJob::dispatch(
                $chunk,
                $request->subject,
                $request->body,
                $ccEmails,
                $bccEmails,
                $isInvitation,
                $request->team_id,
                $request->role_id
            )->delay($delay);
        }

        return back()->with('success', __('¡El motor de envío ha arrancado! Se enviarán ' . count($toEmails) . ' correos divididos en ' . $totalLotes . ' lote(s).'));
    }
}
