<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Mail;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class AgreementSignatureMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Activity $activity;
    public string $guestName;
    public User $inviter;
    public ?string $customMessage;
    public string $signatureUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Activity $activity, string $guestName, User $inviter, ?string $customMessage = null, ?string $guestEmail = null, ?string $prebuiltSignatureUrl = null)
    {
        $this->activity = $activity;
        $this->guestName = $guestName;
        $this->inviter = $inviter;
        $this->customMessage = $customMessage;

        // Si ya se proporciona la URL pre-generada (con el host real), la usamos directamente.
        // Esto evita que en entornos con cola (ShouldQueue) la URL apunte a localhost.
        if ($prebuiltSignatureUrl) {
            $this->signatureUrl = $prebuiltSignatureUrl;
        } else {
            $this->signatureUrl = URL::temporarySignedRoute(
                'agreements.signature.show',
                now()->addDays(30),
                [
                    'team'     => $activity->team_id,
                    'activity' => $activity->id,
                    'email'    => $guestEmail,
                ]
            );
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Solicitud de Firma: ' . $this->activity->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $processedMessage = $this->customMessage;
        
        if ($processedMessage) {
            $processedMessage = str_replace(
                ['[nombre_invitado]', '[mi_nombre]', '[titulo_reunion]'], // Mantenemos las etiquetas para no romper el front
                [$this->guestName, $this->inviter->name, $this->activity->title],
                $processedMessage
            );
        }

        return new Content(
            markdown: 'emails.agreement.signature_invitation',
            with: [
                'activity' => $this->activity,
                'guestName' => $this->guestName,
                'inviter' => $this->inviter,
                'customMessage' => $processedMessage,
                'signatureUrl' => $this->signatureUrl,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
