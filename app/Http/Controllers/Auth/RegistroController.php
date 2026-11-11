<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\EsperaCodigoException;
use App\Http\Controllers\Controller;
use App\Http\Requests\EnviarCodigoRegistroRequest;
use App\Models\Carrera;
use App\Services\CodigoVerificacionService;
use App\Services\CuentaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegistroController extends Controller
{
    public function __construct(
        private CodigoVerificacionService $codigos,
        private CuentaService $cuentas,
    ) {}

    // GET /registro — paso 1: pedir el correo
    public function mostrarCorreo(): Response
    {
        return Inertia::render('auth/registro/Correo');
    }

    // POST /registro/enviar-codigo — paso 1: enviar el código al correo
    public function enviarCodigo(EnviarCodigoRegistroRequest $request): RedirectResponse
    {
        $correo = $request->validated('correo');

        $this->enviarCodigoAlCorreo($correo, 'correo');

        $request->session()->put('registro_correo', $correo);

        return redirect()->route('registro.codigo');
    }

    // GET /registro/codigo — paso 2: ingresar el código
    public function mostrarCodigo(Request $request): RedirectResponse|Response
    {
        $correo = $request->session()->get('registro_correo');

        if (! $correo) {
            return redirect()->route('registro');
        }

        return Inertia::render('auth/registro/Codigo', [
            'correo' => $correo,
            'reenviarEnSegundos' => config('tutorias.codigo_reenvio_segundos'),
        ]);
    }

    // POST /registro/reenviar-codigo — paso 2: volver a enviar el código
    public function reenviarCodigo(Request $request): RedirectResponse
    {
        $correo = $request->session()->get('registro_correo');

        if (! $correo) {
            return redirect()->route('registro');
        }

        $this->enviarCodigoAlCorreo($correo, 'codigo');

        return back();
    }

    // POST /registro/verificar-codigo — paso 2: verificar el código
    public function verificarCodigo(Request $request): RedirectResponse
    {
        $correo = $request->session()->get('registro_correo');

        if (! $correo) {
            return redirect()->route('registro');
        }

        $datos = $request->validate([
            // Siempre como texto: si llegara como número se perderían los ceros a la izquierda
            'codigo' => ['required', 'string', 'digits:6'],
        ], [
            'codigo.required' => 'El código es obligatorio.',
            'codigo.digits' => 'El código debe tener 6 dígitos.',
        ]);

        $token = $this->codigos->verificarCodigo($correo, $datos['codigo'], 'VERIFICAR_CORREO');

        $request->session()->put('registro_token', $token);

        return redirect()->route('registro.completar');
    }

    // GET /registro/completar — paso 3: completar el perfil
    public function mostrarCompletar(Request $request): RedirectResponse|Response
    {
        $correo = $request->session()->get('registro_correo');
        $token = $request->session()->get('registro_token');

        if (! $correo || ! $token) {
            return redirect()->route('registro');
        }

        return Inertia::render('auth/registro/Completar', [
            'correo' => $correo,
            'carreras' => Carrera::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    // POST /registro/completar — paso 3: crear la cuenta e iniciar sesión
    public function completar(Request $request): RedirectResponse
    {
        $token = $request->session()->get('registro_token');

        if (! $token) {
            return redirect()->route('registro');
        }

        $datos = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'carrera_id' => ['required', 'integer', 'exists:carreras,id'],
            'telefono' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'carrera_id.required' => 'La carrera es obligatoria.',
            'carrera_id.exists' => 'La carrera seleccionada no existe.',
            'telefono.required' => 'El teléfono es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        try {
            $user = $this->cuentas->crearEstudianteDesdeRegistro($token, $datos);
        } catch (ValidationException $e) {
            // El registro_token venció o ya se usó: se reinicia el flujo desde el correo.
            $request->session()->forget(['registro_correo', 'registro_token']);

            return redirect()->route('registro')
                ->with('status', 'El registro venció. Empezá de nuevo, por favor.');
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget(['registro_correo', 'registro_token']);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Envía el código y convierte el 429 (EsperaCodigoException) en un error de
     * validación, para que Inertia lo muestre en el formulario en lugar de romperse
     * con una respuesta JSON.
     */
    private function enviarCodigoAlCorreo(string $correo, string $campo): void
    {
        try {
            $this->codigos->enviarCodigo($correo, 'VERIFICAR_CORREO');
        } catch (EsperaCodigoException $e) {
            throw ValidationException::withMessages([
                $campo => ["Espera {$e->segundos} segundos antes de pedir otro código."],
            ]);
        }
    }
}
