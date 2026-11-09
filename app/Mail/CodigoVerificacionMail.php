<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CodigoVerificacionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $codigo,
        public string $tipo,
    ) {}

    // El código va en el asunto para que se vea en la notificación del celular
    public function envelope(): Envelope
    {
        $asunto = $this->tipo === 'RECUPERAR_PASS'
            ? "Código para recuperar tu contraseña: {$this->codigo}"
            : "Tu código de verificación: {$this->codigo}";

        return new Envelope(subject: $asunto);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.codigo-verificacion',
            with: ['minutos' => config('tutorias.codigo_expira_minutos')],
        );
    }
}