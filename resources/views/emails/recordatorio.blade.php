<!DOCTYPE html>
<html>
<body>
    <h1>¡Hola! Tienes una sesión programada</h1>
    <p>Te recordamos que tu próxima mentoría es el día <strong>{{ $sesion->fecha }}</strong>.</p>
    
    <h3>Detalles:</h3>
    <ul>
        <li><strong>Mentor:</strong> {{ $sesion->mentor->nombre }}</li>
        <li><strong>Aprendiz:</strong> {{ $sesion->aprendiz->nombre }}</li>
        <li><strong>Hora:</strong> {{ $sesion->hora_inicio }}</li>
    </ul>

    <p>¡Esperamos que sea una sesión productiva!</p>
</body>
</html>