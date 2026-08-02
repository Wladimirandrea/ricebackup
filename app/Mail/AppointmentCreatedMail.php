<?php
// app/Mail/AppointmentCreatedMail.php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Appointment $appointment;
    public string $recipientType;
    public string $lang;

    public function __construct(
        Appointment $appointment,
        string $recipientType,
        string $lang = 'en'
    ) {
        $this->appointment    = $appointment;
        $this->recipientType  = $recipientType;
        $this->lang           = $lang;
    }

    public function envelope(): Envelope
    {
        $subject = $this->lang === 'es'
            ? 'Nueva Cita Agendada'
            : 'New Appointment Scheduled';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.appointment-created',
            with: [
                'appointment'   => $this->appointment,
                'recipientType' => $this->recipientType,
                'locale'        => $this->lang,
                'isEs'          => $this->lang === 'es',
            ]
        );
    }
}