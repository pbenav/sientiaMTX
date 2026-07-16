<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Team;
use App\Services\AgreementPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class AgreementSignatureController extends Controller
{
    /**
     * Muestra el portal de firma para un miembro INTERNO autenticado.
     * No requiere URL firmada: el usuario ya está autenticado.
     */
    public function showInternal(Request $request, Team $team, Activity $activity)
    {
        if ($activity->type !== 'agreement') {
            abort(404, 'Documento no encontrado o no válido para firma.');
        }

        $user = auth()->user();
        $meta = $activity->metadata ?? [];
        $memberSignatures = $meta['member_signatures'] ?? [];

        // Verificar que el usuario está en la lista de firmantes internos
        $signerEntry = collect($memberSignatures)->first(fn($s) => (int)($s['user_id'] ?? 0) === $user->id);

        if (!$signerEntry) {
            abort(403, 'No estás incluido como firmante de este acuerdo.');
        }

        if (!empty($signerEntry['signed_at'])) {
            return redirect()->route('teams.activities.show', [$team, $activity])
                ->with('info', 'Ya has firmado este acuerdo.');
        }

        // Obtener o generar el PDF (solo los generados o firmados, que contienen 'Acuerdo_')
        $attachment = $activity->attachments()
                               ->where('mime_type', 'application/pdf')
                               ->where('file_name', 'like', '%Acuerdo_%')
                               ->latest()
                               ->first();

        if (!$attachment) {
            $pdfService = app(AgreementPdfService::class);
            $attachment = $pdfService->generateAndAttach($activity, $team);
        }

        return view('agreements.signature.show', [
            'team'        => $team,
            'activity'    => $activity,
            'attachment'  => $attachment,
            'signerEmail' => $user->email,
            'isInternal'  => true,
        ]);
    }

    /**
     * Muestra la vista pública de firma para un invitado externo.
     * Utiliza Signed Routes para verificar la autenticidad sin login.
     */
    public function show(Request $request, Team $team, Activity $activity)
    {
        // 1. Validar que la URL está firmada válidamente.
        // Hacemos una validación manual y flexible para soportar entornos con proxies (HTTP vs HTTPS)
        // y para soportar enlaces antiguos generados con absolute=true.
        $isValid = $request->hasValidSignature() || $request->hasValidSignature(false);

        if (!$isValid) {
            // Comprobar si forzando la validación en HTTPS pasa (por si el proxy eliminó X-Forwarded-Proto)
            $originalRequestUrl = $request->fullUrl();
            if (str_starts_with($originalRequestUrl, 'http://')) {
                $httpsUrl = str_replace('http://', 'https://', $originalRequestUrl);
                $request->server->set('HTTPS', 'on');
                $request->server->set('SERVER_PORT', 443);
                $isValid = $request->hasValidSignature();
                // Restaurar
                $request->server->set('HTTPS', 'off');
                $request->server->set('SERVER_PORT', 80);
            }
        }

        if (!$isValid) {
            abort(403, 'Enlace no válido o caducado.');
        }

        // 2. Verificar que la actividad es de tipo 'agreement'
        if ($activity->type !== 'agreement') {
            abort(404, 'Documento no encontrado o no válido para firma.');
        }

        // 3. Obtener el email del firmante desde los parámetros de la URL
        $signerEmail = $request->query('email');
        if (!$signerEmail) {
            abort(403, 'Falta el identificador del firmante.');
        }

        // 4. Buscar el PDF base o el último PDF firmado. Si no existe, generarlo.
        $attachment = $activity->attachments()
                               ->where('mime_type', 'application/pdf')
                               ->where('file_name', 'like', '%Acuerdo_%')
                               ->latest()
                               ->first();
        
        // Si no hay attachment, lo podemos auto-generar (aunque lo ideal es que ya estuviese generado)
        if (!$attachment) {
            $pdfService = app(AgreementPdfService::class);
            $attachment = $pdfService->generateAndAttach($activity, $team);
        }

        // Retornar la vista de firma
        return view('agreements.signature.show', [
            'team'        => $team,
            'activity'    => $activity,
            'attachment'  => $attachment,
            'signerEmail' => $signerEmail,
        ]);
    }

    /**
     * Procesa el resultado de la firma de Autofirma (recepción del PDF firmado).
     */
    public function processSignature(Request $request, Team $team, Activity $activity)
    {
        $request->validate([
            'signed_file' => 'required|file|mimes:pdf',
            'signer_email' => 'required|email'
        ]);

        $file = $request->file('signed_file');
        
        // 3. Guardar el nuevo attachment (versión firmada)
        $path = $file->store("activities/{$activity->id}/signed", 'local');

        \Illuminate\Support\Facades\DB::transaction(function () use ($activity, $request, $path, $file) {
            // Eliminar versiones previas de archivos firmados (mantener solo el último)
            $oldSignedAttachments = $activity->attachments()
                ->where('file_path', 'like', "activities/{$activity->id}/signed/%")
                ->get();
                
            foreach ($oldSignedAttachments as $oldAttachment) {
                \Illuminate\Support\Facades\Storage::disk($oldAttachment->disk)->delete($oldAttachment->file_path);
                $oldAttachment->delete();
            }

            // Crear el nuevo adjunto
            $activity->attachments()->create([
                'uploaded_by_id' => $activity->created_by_id, // Atribuido al creador original
                'file_name'      => now()->format('Y-m-d-H-i-s') . '-Acuerdo_' . str()->slug($activity->title) . '.pdf',
                'file_path'      => $path,
                'disk'           => 'local',
                'mime_type'      => 'application/pdf',
                'file_size'      => $file->getSize(),
            ]);

            $lockedActivity = Activity::lockForUpdate()->find($activity->id);
            $meta = $lockedActivity->metadata ?? [];
            $meta['signatures'][] = [
                'email' => $request->signer_email,
                'signed_at' => now()->format('Y-m-d H:i:s'),
            ];
            
            // Actualizar a guests también si existía
            if (isset($meta['guests'])) {
                foreach ($meta['guests'] as &$guest) {
                    if ($guest['email'] === $request->signer_email) {
                        $guest['signed_at'] = now()->format('Y-m-d H:i:s');
                    }
                }
            }

            // Actualizar a member_signatures si corresponde a un miembro interno
            if (isset($meta['member_signatures'])) {
                foreach ($meta['member_signatures'] as &$sig) {
                    $u = \App\Models\User::find($sig['user_id']);
                    if ($u && $u->email === $request->signer_email) {
                        $sig['signed_at'] = now()->format('Y-m-d H:i:s');
                    }
                }
            }
            
            $lockedActivity->updateQuietly(['metadata' => $meta]);
        });

        $redirect = route('agreements.signature.success');
        if (auth()->check()) {
            $redirect = route('teams.activities.show', [$team, $activity]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Documento firmado correctamente.',
            'redirect' => $redirect
        ]);
    }

    /**
     * Sirve el documento PDF de forma segura bajo una URL firmada.
     */
    public function download(Request $request, Team $team, Activity $activity)
    {
        if ($activity->type !== 'agreement') {
            abort(404, 'Documento no encontrado.');
        }

        $isValid = $request->hasValidSignature() || $request->hasValidSignature(false);
        if (!$isValid) {
            $originalRequestUrl = $request->fullUrl();
            if (str_starts_with($originalRequestUrl, 'http://')) {
                $request->server->set('HTTPS', 'on');
                $request->server->set('SERVER_PORT', 443);
                $isValid = $request->hasValidSignature();
                $request->server->set('HTTPS', 'off');
                $request->server->set('SERVER_PORT', 80);
            }
        }

        if (!$isValid) {
            abort(403, 'Enlace no válido o caducado.');
        }

        $attachment = $activity->attachments()
                               ->where('mime_type', 'application/pdf')
                               ->where('file_name', 'like', '%Acuerdo_%')
                               ->latest()
                               ->first();

        if (!$attachment || !\Illuminate\Support\Facades\Storage::disk($attachment->disk)->exists($attachment->file_path)) {
            abort(404, 'Archivo físico no encontrado.');
        }

        return \Illuminate\Support\Facades\Storage::disk($attachment->disk)->response(
            $attachment->file_path,
            $attachment->file_name,
            [
                'Content-Disposition' => 'inline; filename="' . addslashes($attachment->file_name) . '"'
            ]
        );
    }

    /**
     * Reenvía el email de invitación de firma a un invitado específico.
     * Regenera una nueva URL firmada usando el host real de la petición,
     * evitando que el enlace apunte a APP_URL (que puede ser localhost).
     */
    public function resendInvitation(Request $request, Team $team, Activity $activity)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        if ($activity->type !== 'agreement') {
            return response()->json(['error' => 'Actividad no válida.'], 422);
        }

        $guestEmail = $request->email;
        $meta       = $activity->metadata ?? [];
        $guests     = $meta['guests'] ?? [];

        $guest = collect($guests)->firstWhere('email', $guestEmail);

        if (!$guest || empty($guest['email'])) {
            return response()->json(['error' => 'Firmante no encontrado.'], 404);
        }

        if (!empty($guest['signed_at'])) {
            return response()->json(['error' => 'Este firmante ya ha completado la firma.'], 422);
        }

        // Forzar app.url al host real de la request para que URL::temporarySignedRoute
        // genere un enlace accesible (no apuntando a localhost del .env).
        $originalAppUrl = config('app.url');
        config(['app.url' => $request->getSchemeAndHttpHost()]);
        URL::forceRootUrl($request->getSchemeAndHttpHost());

        try {
            // Envío síncrono (sin cola) para evitar que la URL se genere
            // en un contexto diferente donde app.url vuelva a ser el del .env.
            Mail::to($guestEmail)->send(
                new \App\Mail\AgreementSignatureMail(
                    $activity,
                    $guest['name'] ?? 'Firmante',
                    auth()->user(),
                    null,
                    $guestEmail
                )
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to resend agreement invitation to {$guestEmail}: " . $e->getMessage());
            return response()->json(['error' => 'No se pudo enviar el email. Inténtalo de nuevo.'], 500);
        } finally {
            // Restaurar siempre el valor original, pase lo que pase.
            config(['app.url' => $originalAppUrl]);
            URL::forceRootUrl($originalAppUrl);
        }

        return response()->json(['success' => true, 'message' => 'Invitación reenviada correctamente a ' . $guestEmail . '.']);
    }

    /**
     * Firma el acuerdo como miembro interno autenticado.
     * No necesita URL firmada: el usuario ya está autenticado y asignado.
     */
    public function signAsInternalMember(Request $request, Team $team, Activity $activity)
    {
        if ($activity->type !== 'agreement') {
            return response()->json(['error' => 'Actividad no válida.'], 422);
        }

        $user = auth()->user();

        return \Illuminate\Support\Facades\DB::transaction(function () use ($activity, $team, $user) {
            $lockedActivity = Activity::lockForUpdate()->find($activity->id);
            $meta = $lockedActivity->metadata ?? [];
            $memberSignatures = $meta['member_signatures'] ?? [];

            // Verificar que el usuario está en la lista de firmantes internos
            $index = collect($memberSignatures)->search(fn($s) => (int)($s['user_id'] ?? 0) === $user->id);

            if ($index === false) {
                return response()->json(['error' => 'No estás incluido como firmante de este acuerdo.'], 403);
            }

            if (!empty($memberSignatures[$index]['signed_at'])) {
                return response()->json(['error' => 'Ya has firmado este acuerdo.'], 422);
            }

            $memberSignatures[$index]['signed_at'] = now()->format('Y-m-d H:i:s');
            $meta['member_signatures'] = $memberSignatures;
            $lockedActivity->updateQuietly(['metadata' => $meta]);

            // Eliminar los PDFs base anteriores generados automáticamente (no los adjuntos subidos por usuarios ni los firmados externamente)
            $oldBaseAttachments = $lockedActivity->attachments()
                ->where('mime_type', 'application/pdf')
                ->where('file_name', 'like', 'Acuerdo_%')
                ->where('file_path', 'not like', '%/signed/%')
                ->get();
                
            foreach ($oldBaseAttachments as $old) {
                \Illuminate\Support\Facades\Storage::disk($old->disk)->delete($old->file_path);
                $old->delete();
            }

            // Regenerar el PDF con la nueva firma
            $pdfService = app(\App\Services\AgreementPdfService::class);
            $pdfService->generateAndAttach($lockedActivity, $team);

            return response()->json([
                'success'   => true,
                'message'   => 'Has firmado el acuerdo correctamente.',
                'signed_at' => $memberSignatures[$index]['signed_at'],
            ]);
        });
    }

    /**
     * Muestra la página de éxito.
     */
    public function success()
    {
        return view('agreements.signature.success');
    }
}
