<!DOCTYPE html>
<html lang="es">
<body style="font-family: Arial, sans-serif; color: #222;">
    <h2>Tu tutoría está confirmada</h2>
    <p>Hola, {{ $tutoria->estudiante->name }}.</p>
    <p>Reservaste una tutoría con {{ $tutoria->tutor->name }}.</p>
    <ul>
        <li>Materia: {{ $tutoria->materia->nombre }}</li>
        <li>Tema: {{ $tutoria->tema }}</li>
        <li>Fecha: {{ $tutoria->fecha->format('d/m/Y') }}</li>
        <li>Horario: {{ $tutoria->hora_inicio }}:00 a {{ $tutoria->hora_inicio + 1 }}:00 (hora de Honduras)</li>
        <li>Modalidad: {{ $tutoria->modalidad }}</li>
        @if ($tutoria->lugar_o_enlace)
            <li>Referencia o enlace: {{ $tutoria->lugar_o_enlace }}</li>
        @endif
    </ul>
    @if ($tutoria->modalidad === 'PRESENCIAL')
        <p>Acuerda el punto de encuentro con tu tutor por un medio externo.</p>
    @endif
    <p>Teléfono del tutor: {{ $tutoria->tutor->telefono }}</p>
</body>
</html>
