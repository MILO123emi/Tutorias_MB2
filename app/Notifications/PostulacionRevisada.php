<?php

namespace App\Notifications;

use App\Models\PerfilTutor;
use App\Models\User;
use Illuminate\Notifications\Notification;

class PostulacionRevisada extends Notification
{
    public function __construct(private PerfilTutor $perfil, private string $estado, private ?string $motivo) {}

    /** @return array<int, string> */
    public function via(User $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(User $notifiable): array
    {
        $aprobada = $this->estado === 'APROBADO';

        return [
            'perfil_tutor_id' => $this->perfil->id,
            'estado' => $this->estado,
            'titulo' => $aprobada ? '¡Ya eres tutor!' : 'Postulación rechazada',
            'mensaje' => $aprobada ? 'Tu postulación como tutor fue aprobada.' : 'Tu postulación como tutor fue rechazada.',
            'motivo' => $this->motivo,
        ];
    }
}
