<?php

namespace App\Policies;

use App\Models\Tutoria;
use App\Models\User;

class TutoriaPolicy
{
    public function view(User $user, Tutoria $tutoria): bool
    {
        return $user->esAdmin() || $user->id === $tutoria->estudiante_id || $user->id === $tutoria->tutor_id;
    }

    public function cancel(User $user, Tutoria $tutoria): bool
    {
        return ! $user->esAdmin() && ($user->id === $tutoria->estudiante_id || $user->id === $tutoria->tutor_id);
    }

    public function confirmarAsistencia(User $user, Tutoria $tutoria): bool
    {
        return ! $user->esAdmin() && $user->id === $tutoria->tutor_id;
    }

    public function reportarInasistencia(User $user, Tutoria $tutoria): bool
    {
        return $this->cancel($user, $tutoria);
    }

    public function calificar(User $user, Tutoria $tutoria): bool
    {
        return ! $user->esAdmin() && $user->id === $tutoria->estudiante_id;
    }

    public function create(User $user): bool
    {
        return $user->rol === 'ESTUDIANTE';
    }
}
