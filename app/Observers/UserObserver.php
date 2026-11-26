<?php

namespace App\Observers;

use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Support\Facades\Auth;

// Rol y estado de la cuenta solo cambian con forceFill, así que se vigilan aquí
// y no en cada lugar que los toca.
class UserObserver
{
    public function __construct(private BitacoraService $bitacora) {}

    public function created(User $user): void
    {
        $this->bitacora->registrar($this->actor() ?? $user, 'USUARIO_REGISTRADO', 'users', $user->id,
            "Se creó la cuenta de {$user->name} ({$user->email}).");
    }

    public function updated(User $user): void
    {
        if ($user->wasChanged('rol')) {
            $this->bitacora->registrar($this->actor(), 'USUARIO_ROL_CAMBIADO', 'users', $user->id,
                "El rol de {$user->name} pasó de {$user->getOriginal('rol')} a {$user->rol}.",
                requiereAtencion: true);
        }

        if ($user->wasChanged('activo')) {
            $user->activo
                ? $this->bitacora->registrar($this->actor(), 'USUARIO_REACTIVADO', 'users', $user->id, "Se reactivó la cuenta de {$user->name}.")
                : $this->bitacora->registrar($this->actor(), 'USUARIO_SUSPENDIDO', 'users', $user->id, "Se suspendió la cuenta de {$user->name}.");
        }
    }

    // null cuando el cambio viene de consola o de un seeder
    private function actor(): ?User
    {
        $actor = Auth::user();

        return $actor instanceof User ? $actor : null;
    }
}
