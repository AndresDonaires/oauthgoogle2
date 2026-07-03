<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Nueva Solicitud de Mentoría</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:32px 0;">
    <tr>
      <td align="center">
        <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">

          <!-- Header -->
          <tr>
            <td style="background:#e6a23c;padding:28px 32px;text-align:center;">
              <p style="margin:0;font-size:22px;font-weight:700;color:#ffffff;">
                🔔 Nueva Solicitud de Mentoría
              </p>
              <p style="margin:6px 0 0;font-size:14px;color:#fdf3e2;">
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
                <strong>{{ $nombre_aprendiz }}</strong> te ha solicitado una nueva sesión de mentoría. Revisa los detalles y confírmala cuando puedas.
              </p>

              <!-- Detalles de la sesión -->
              <table width="100%" cellpadding="0" cellspacing="0"
                     style="background:#fdf6ec;border:1px solid #f5dab1;border-radius:8px;margin-bottom:24px;">
                <tr>
                  <td style="padding:20px 24px;">
                    <p style="margin:0 0 12px;font-size:13px;font-weight:700;color:#e6a23c;text-transform:uppercase;letter-spacing:.5px;">
                      Detalles de la solicitud
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
                        <td style="color:#888;font-weight:600;">Aprendiz</td>
                        <td>{{ $nombre_aprendiz }}</td>
                      </tr>
                      @if($observaciones)
                      <tr>
                        <td style="color:#888;font-weight:600;vertical-align:top;">Tema</td>
                        <td>{{ $observaciones }}</td>
                      </tr>
                      @endif
                    </table>
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 12px;font-size:14px;color:#555;">
                Ingresa a la plataforma para confirmar la sesión (deberás pegar el link de Google Meet):
              </p>
              <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
                <tr>
                  <td align="center">
                    <a href="{{ $frontend_url }}/sesiones"
                       style="display:inline-block;background:#e6a23c;color:#fff;text-decoration:none;
                              padding:12px 32px;border-radius:8px;font-size:15px;font-weight:600;">
                      Ver solicitud
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
