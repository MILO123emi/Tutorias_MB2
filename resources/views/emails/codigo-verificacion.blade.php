<!DOCTYPE html>
<html lang="es">
<body style="font-family: Arial, sans-serif; color: #222;">
    <h2>Tutorías de becados</h2>

    @if ($tipo === 'RECUPERAR_PASS')
        <p>Usa este código para restablecer tu contraseña:</p>
    @else
        <p>Usa este código para verificar tu correo:</p>
    @endif

    <p style="font-size: 32px; letter-spacing: 6px; font-weight: bold;">{{ $codigo }}</p>

    <p>El código vence en {{ $minutos }} minutos. Si no lo pediste tú, ignora este mensaje.</p>
</body>
</html>