<?php

namespace App\Services;

use App\Models\SolicitudFoto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
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
}
