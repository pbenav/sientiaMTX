<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Appointment  $appointment  Cita confirmada
     */
    public function __construct(public Appointment $appointment) {}

    /**
     * Obtener el sobre del correo.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '✅ ' . __('Cita Confirmada') . ' — ' . $this->appointment->localizador,
        );
    }

    /**
     * Obtener el contenido del correo.
     */
    public function content(): Content
    {
        return new Content(markdown: 'mail.appointments.confirmed');
    }
}
