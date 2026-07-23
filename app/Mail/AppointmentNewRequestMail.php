<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentNewRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Appointment  $appointment  Cita nueva solicitada
     */
    public function __construct(public Appointment $appointment) {}

    /**
     * Obtener el sobre del correo.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '📅 ' . __('Nueva Cita Previa') . ' — ' . $this->appointment->localizador,
        );
    }

    /**
     * Obtener el contenido del correo.
     */
    public function content(): Content
    {
        return new Content(markdown: 'mail.appointments.new-request');
    }
}
