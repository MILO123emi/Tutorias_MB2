<?php

namespace App\Notifications;

use App\Models\Tutoria;
use App\Models\User;
use Illuminate\Notifications\Notification;

class TutoriaReservada extends Notification
{
    public function __construct(public Tutoria $tutoria) {}

    /** @return array<int, string> */
    public function via(User $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(User $notifiable): array
    {
        return [
            'tutoria_id' => $this->tutoria->id,
            'titulo' => 'Nueva tutoría reservada',
            'mensaje' => $this->tutoria->estudiante->name.' reservó una tutoría de '.$this->tutoria->materia->nombre.'.',
            'fecha' => $this->tutoria->fecha->format('Y-m-d'),
            'hora_inicio' => $this->tutoria->hora_inicio,
        ];
    }
}
