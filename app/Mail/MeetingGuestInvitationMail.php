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

class MeetingGuestInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Activity $activity;
    public string $guestName;
    public User $inviter;
    public ?string $customMessage;

    /**
     * Create a new message instance.
     */
    public function __construct(Activity $activity, string $guestName, User $inviter, ?string $customMessage = null)
    {
        $this->activity = $activity;
        $this->guestName = $guestName;
        $this->inviter = $inviter;
        $this->customMessage = $customMessage;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invitación a Reunión: ' . $this->activity->title,
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
                ['[nombre_invitado]', '[mi_nombre]', '[titulo_reunion]'],
                [$this->guestName, $this->inviter->name, $this->activity->title],
                $processedMessage
            );
        }

        return new Content(
            markdown: 'emails.meeting.guest_invitation',
            with: [
                'activity' => $this->activity,
                'guestName' => $this->guestName,
                'inviter' => $this->inviter,
                'customMessage' => $processedMessage,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
