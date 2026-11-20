<?php

namespace App\Mail;

use App\Models\Tutoria;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TutoriaReservadaMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Tutoria $tutoria) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Tu tutoría está confirmada');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tutoria-reservada');
    }
}
