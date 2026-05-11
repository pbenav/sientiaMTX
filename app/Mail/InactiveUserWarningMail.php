<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InactiveUserWarningMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public int $gracePeriodDays;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, int $gracePeriodDays)
    {
        $this->user = $user;
        $this->gracePeriodDays = $gracePeriodDays;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚠️ Aviso Importante: Tu cuenta en ' . config('app.name') . ' se desactivará pronto',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.inactive-warning',
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
