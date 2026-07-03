<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sesión Completada</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:32px 0;">
    <tr>
      <td align="center">
        <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">

          <!-- Header -->
          <tr>
            <td style="background:#67c23a;padding:28px 32px;text-align:center;">
              <p style="margin:0;font-size:22px;font-weight:700;color:#ffffff;">
                🎓 Sesión Completada
              </p>
              <p style="margin:6px 0 0;font-size:14px;color:#e3f6d8;">
                Plataforma Mentoría
              </p>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:32px;">

              <p style="margin:0 0 8px;font-size:16px;color:#333;">
                Hola <strong>{{ $nombre }}</strong>,
              </p>
              <p style="margin:0 0 24px;font-size:14px;color:#555;line-height:1.6;">
                Tu mentor <strong>{{ $nombre_mentor }}</strong> ha marcado como completada tu sesión de mentoría. ¡Esperamos que haya sido de mucha ayuda!
              </p>

              <!-- Detalles de la sesión -->
              <table width="100%" cellpadding="0" cellspacing="0"
                     style="background:#f0f9eb;border:1px solid #c2e7b0;border-radius:8px;margin-bottom:24px;">
                <tr>
                  <td style="padding:20px 24px;">
                    <p style="margin:0 0 12px;font-size:13px;font-weight:700;color:#67c23a;text-transform:uppercase;letter-spacing:.5px;">
                      Detalles de la sesión
                    </p>
                    <table width="100%" cellpadding="4" cellspacing="0" style="font-size:14px;color:#333;">
                      <tr>
                        <td style="width:120px;color:#888;font-weight:600;">Fecha</td>
                        <td>{{ $fecha }}</td>
                      </tr>
                      <tr>
                        <td style="color:#888;font-weight:600;">Horario</td>
                        <td>{{ $hora_inicio }} – {{ $hora_fin }}</td>
                      </tr>
                      <tr>
                        <td style="color:#888;font-weight:600;">Mentor</td>
                        <td>{{ $nombre_mentor }}</td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              <!-- Llamado a la acción -->
              <p style="margin:0 0 12px;font-size:14px;color:#555;">
                Ya puedes calificar esta sesión y dejarle un comentario a tu mentor:
              </p>
              <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
                <tr>
                  <td align="center">
                    <a href="{{ $frontend_url }}/valoraciones"
                       style="display:inline-block;background:#67c23a;color:#fff;text-decoration:none;
                              padding:12px 32px;border-radius:8px;font-size:15px;font-weight:600;">
                      Valorar sesión
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-size:13px;color:#aaa;text-align:center;">
                Este es un correo automático de la Plataforma Mentoría. No respondas este mensaje.
              </p>

            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:#f9fafb;border-top:1px solid #eee;padding:16px 32px;text-align:center;">
              <p style="margin:0;font-size:12px;color:#bbb;">
                © {{ date('Y') }} Plataforma Mentoría — Todos los derechos reservados
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
