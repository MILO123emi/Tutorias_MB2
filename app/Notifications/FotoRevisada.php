<?php

namespace App\Notifications;

use App\Models\SolicitudFoto;
use App\Models\User;
use Illuminate\Notifications\Notification;

class FotoRevisada extends Notification
{
    public function __construct(private SolicitudFoto $solicitud, private string $estado, private ?string $motivo) {}

    /** @return array<int, string> */
    public function via(User $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(User $notifiable): array
    {
        $aprobada = $this->estado === 'APROBADA';

        return [
            'solicitud_id' => $this->solicitud->id,
            'estado' => $this->estado,
            'titulo' => $aprobada ? 'Foto de perfil aprobada' : 'Foto de perfil rechazada',
            'mensaje' => $aprobada ? 'Tu foto de perfil fue aprobada.' : 'Tu foto de perfil fue rechazada.',
            'motivo' => $this->motivo,
        ];
    }
}
