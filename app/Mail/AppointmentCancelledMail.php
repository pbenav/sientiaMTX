<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentCancelledMail extends Mailable
{
    use Queueable;

    /**
     * @param  Appointment  $appointment  Cita cancelada
     * @param  string|null  $reason  Motivo de la cancelación
     */
    public function __construct(
        public Appointment $appointment,
        public ?string $reason = null
    ) {
        $this->reason = $reason ?? $appointment->cancellation_reason;
    }

    /**
     * Obtener el sobre del correo.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '❌ Cita Cancelada — ' . $this->appointment->localizador,
        );
    }

    /**
     * Obtener el contenido del correo.
     */
    public function content(): Content
    {
        return new Content(markdown: 'mail.appointments.cancelled');
    }
}
