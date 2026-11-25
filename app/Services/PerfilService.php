<?php

namespace App\Services;

use App\Models\SolicitudFoto;
use App\Models\User;
use App\Notifications\FotoRevisada;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class PerfilService
{
    public function __construct(private BitacoraService $bitacora) {}

    /**
     * Actualiza los datos propios. El correo no se toca (RF-06).
     *
     * @param  array{name: string, telefono: string, carrera_id: int}  $datos
     */
    public function actualizar(User $user, array $datos): User
    {
        $user->update($datos);

        return $user->refresh();
    }

    /**
     * Verifica la contraseña actual, la cambia y cierra las demás sesiones de la app
     * (deja viva la del dispositivo que hizo el cambio).
     */
    public function cambiarPassword(User $user, string $actual, string $nueva): void
    {
        if (! Hash::check($actual, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['La contraseña actual no coincide.'],
            ]);
        }

        $user->forceFill(['password' => $nueva])->save();

        $tokenActual = $user->currentAccessToken();
        $idActual = $tokenActual instanceof PersonalAccessToken ? $tokenActual->getKey() : null;

        $user->tokens()
            ->when($idActual, fn ($query) => $query->where('id', '!=', $idActual))
            ->delete();
    }

    /**
     * Sube la foto validada al disco public. Si el usuario no tiene foto se aplica
     * directo; si ya tiene, queda una solicitud PENDIENTE y se conserva la anterior (RN-13).
     *
     * @return array{estado: string, foto_url?: string, solicitud?: SolicitudFoto}
     */
    public function subirFoto(User $user, UploadedFile $foto): array
    {
        $ruta = $foto->store('fotos', 'public');
        $url = Storage::disk('public')->url($ruta);

        if (! $user->foto_url) {
            $user->update(['foto_url' => $url]);

            return ['estado' => 'APLICADA', 'foto_url' => $url];
        }

        $solicitud = SolicitudFoto::create([
            'user_id' => $user->id,
            'foto_nueva_url' => $url,
        ]);

        $this->bitacora->registrar(
            $user,
            'FOTO_SOLICITADA',
            'solicitudes_foto',
            $solicitud->id,
            'Cambio de foto pendiente de aprobación',
            true,
        );

        return ['estado' => 'PENDIENTE', 'solicitud' => $solicitud];
    }

    /**
     * La solicitud de foto pendiente del usuario, si hay.
     */
    public function solicitudPendiente(User $user): ?SolicitudFoto
    {
        return SolicitudFoto::where('user_id', $user->id)->pendientes()->latest()->first();
    }

    /** @return LengthAwarePaginator<int, SolicitudFoto> */
    public function fotosPendientes(): LengthAwarePaginator
    {
        return SolicitudFoto::pendientes()->with('user.perfilTutor')
            ->orderBy('created_at')->orderBy('id')->paginate(15);
    }

    public function aprobarFoto(User $admin, SolicitudFoto $solicitud): SolicitudFoto
    {
        return DB::transaction(function () use ($admin, $solicitud): SolicitudFoto {
            $solicitud = SolicitudFoto::whereKey($solicitud->id)->lockForUpdate()->firstOrFail();
            abort_unless($solicitud->estado === 'PENDIENTE', 409, 'Esta foto ya fue revisada.');
            $usuario = User::whereKey($solicitud->user_id)->lockForUpdate()->firstOrFail();
            $usuario->forceFill(['foto_url' => $solicitud->foto_nueva_url])->save();
            $solicitud->forceFill(['estado' => 'APROBADA', 'revisado_por' => $admin->id])->save();

            $otras = SolicitudFoto::where('user_id', $usuario->id)->pendientes()
                ->whereKeyNot($solicitud->id)->lockForUpdate()->get();
            foreach ($otras as $otra) {
                $otra->forceFill([
                    'estado' => 'RECHAZADA', 'motivo_rechazo' => 'Se aprobó otra foto de perfil.',
                    'revisado_por' => $admin->id,
                ])->save();
                $this->bitacora->marcarRevisados($admin, 'FOTO_SOLICITADA', 'solicitudes_foto', $otra->id);
            }

            $this->bitacora->marcarRevisados($admin, 'FOTO_SOLICITADA', 'solicitudes_foto', $solicitud->id);
            $this->bitacora->registrar($admin, 'FOTO_APROBADA', 'solicitudes_foto', $solicitud->id,
                "Aprobó la foto de {$usuario->name}.");
            $usuario->notify(new FotoRevisada($solicitud, 'APROBADA', null));

            return $solicitud->load('user.perfilTutor');
        }, 3);
    }

    public function rechazarFoto(User $admin, SolicitudFoto $solicitud, string $motivo): SolicitudFoto
    {
        return DB::transaction(function () use ($admin, $solicitud, $motivo): SolicitudFoto {
            $solicitud = SolicitudFoto::whereKey($solicitud->id)->lockForUpdate()->firstOrFail();
            abort_unless($solicitud->estado === 'PENDIENTE', 409, 'Esta foto ya fue revisada.');
            $usuario = User::whereKey($solicitud->user_id)->lockForUpdate()->firstOrFail();
            $solicitud->forceFill([
                'estado' => 'RECHAZADA', 'motivo_rechazo' => $motivo, 'revisado_por' => $admin->id,
            ])->save();
            $this->bitacora->marcarRevisados($admin, 'FOTO_SOLICITADA', 'solicitudes_foto', $solicitud->id);
            $this->bitacora->registrar($admin, 'FOTO_RECHAZADA', 'solicitudes_foto', $solicitud->id,
                "Rechazó la foto de {$usuario->name}.");
            $usuario->notify(new FotoRevisada($solicitud, 'RECHAZADA', $motivo));

            return $solicitud->load('user.perfilTutor');
        }, 3);
    }
}
