<?php

namespace App\Mail;

use App\Models\Sesion; // <--- Importa tu modelo
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecordatorioSesion extends Mailable
{
    use Queueable, SerializesModels;

    public $sesion; // Esta variable estará disponible en la vista

    public function __construct(Sesion $sesion)
    {
        $this->sesion = $sesion;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recordatorio de tu próxima sesión',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recordatorio', // Asegúrate de crear este archivo
        );
    }
}