<?php

namespace App\Notifications;

use App\Models\Citacion;
use App\Models\User;
use Illuminate\Notifications\Notification;

class CitacionRecibida extends Notification
{
    public function __construct(private Citacion $citacion) {}

    /** @return array<int, string> */
    public function via(User $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(User $notifiable): array
    {
        return [
            'citacion_id' => $this->citacion->id,
            'titulo' => 'Tienes una citación',
            'mensaje' => $this->citacion->mensaje,
            'fecha_cita' => $this->citacion->fecha_cita?->toIso8601String(),
        ];
    }
}
