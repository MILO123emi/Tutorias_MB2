<?php

namespace App\Observers;

use App\Models\Parametro;
use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Support\Facades\Auth;

class ParametroObserver
{
    public function __construct(private BitacoraService $bitacora) {}

    // Solo cambios: la creación viene del seeder y no aporta nada
    public function updated(Parametro $parametro): void
    {
        if (! $parametro->wasChanged('valor')) {
            return;
        }

        $actor = Auth::user();
        $anterior = $parametro->getOriginal('valor') ?? 'sin límite';
        $nuevo = $parametro->valor ?? 'sin límite';

        $this->bitacora->registrar($actor instanceof User ? $actor : null, 'PARAMETRO_ACTUALIZADO', 'parametros', null,
            "El parámetro {$parametro->clave} cambió de {$anterior} a {$nuevo}.");
    }
}
