<?php

namespace App\Actions\Activities;

use App\Models\Activity;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ResendMeetingInvitationAction
{
    public function execute(Activity $activity, string $guestEmail): array
    {
        $meta = $activity->metadata ?? [];
        $guests = $meta['guests'] ?? [];

        $guest = collect($guests)->firstWhere('email', $guestEmail);

        if (!$guest) {
            return ['success' => false, 'error' => 'Invitado no encontrado en la lista.', 'code' => 404];
        }

        try {
            Mail::to($guestEmail)->send(
                new \App\Mail\MeetingGuestInvitationMail(
                    $activity,
                    $guest['name'] ?? 'Invitado',
                    auth()->user(),
                    $meta['invitation_message'] ?? null
                )
            );

            $msgLabel = $activity->type === 'reminder' ? 'Recordatorio enviado' : 'Invitación enviada';
            return [
                'success' => true,
                'message' => "{$msgLabel} con éxito a {$guestEmail}."
            ];
        } catch (\Exception $e) {
            Log::error("Failed to resend guest invitation to {$guestEmail}: " . $e->getMessage());
            return ['success' => false, 'error' => 'No se pudo enviar el correo: ' . $e->getMessage(), 'code' => 500];
        }
    }
}
