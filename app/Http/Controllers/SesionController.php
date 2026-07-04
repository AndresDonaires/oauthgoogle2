<?php

namespace App\Http\Controllers;

use App\Models\Sesion;
use App\Models\Usuario;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class SesionController extends Controller
{
    private function queryMisSesiones($usuario)
    {
        return Sesion::where(function ($q) use ($usuario) {
            $q->where('aprendiz_id', $usuario->id)
              ->orWhere('mentor_id', $usuario->id);
        });
    }

    public function index(Request $request)
    {
        $usuario = $request->attributes->get('usuario_auth');
        $estado  = $request->query('estado');
        $perPage = 10;

        // Modo "todas": devuelve el arreglo completo sin paginar, para vistas
        // que necesitan calcular sus propios agregados (Dashboard, Mis Valoraciones).
        if ($request->boolean('todas')) {
            $sesiones = $this->queryMisSesiones($usuario)
                ->with(['aprendiz:id,nombre', 'mentor:id,nombre'])
                ->orderByDesc('fecha')
                ->orderByDesc('hora_inicio')
                ->get();

            return response()->json($sesiones);
        }

        // Contadores por estado (sobre el total, no solo la página actual)
        $conteos = $this->queryMisSesiones($usuario)
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $contadores = [
            'todas'      => (int) $conteos->sum(),
            'pendiente'  => (int) ($conteos['pendiente'] ?? 0),
            'confirmada' => (int) ($conteos['confirmada'] ?? 0),
            'completada' => (int) ($conteos['completada'] ?? 0),
            'cancelada'  => (int) ($conteos['cancelada'] ?? 0),
        ];

        $query = $this->queryMisSesiones($usuario)
            ->with(['aprendiz:id,nombre', 'mentor:id,nombre']);

        if ($estado && $estado !== 'todas') {
            $query->where('estado', $estado);
        }

        $paginado = $query
            ->orderByDesc('fecha')
            ->orderByDesc('hora_inicio')
            ->paginate($perPage)
            ->appends($request->query());

        $respuesta = $paginado->toArray();
        $respuesta['contadores'] = $contadores;

        return response()->json($respuesta);
    }

    public function show($id)
    {
        $sesion = Sesion::with(['aprendiz:id,nombre', 'mentor:id,nombre'])->find($id);
        if (!$sesion) {
            return response()->json(['mensaje' => 'Sesión no encontrada'], 404);
        }
        return response()->json($sesion);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'mentor_id'     => 'required|exists:usuarios,id',
                'aprendiz_id'   => 'required|exists:usuarios,id',
                'fecha'         => 'required|date',
                'hora_inicio'   => 'required',
                'hora_fin'      => 'required',
                'observaciones' => 'nullable|string|max:500',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['mensaje' => 'Error de validación', 'errores' => $e->errors()], 422);
        }

        // Solo sesiones activas (pendiente o confirmada) bloquean el horario
        $conflicto = Sesion::where('mentor_id', $request->mentor_id)
            ->where('fecha', $request->fecha)
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->where(function ($query) use ($request) {
                $query->where('hora_inicio', '<', $request->hora_fin)
                      ->where('hora_fin', '>', $request->hora_inicio);
            })->exists();

        if ($conflicto) {
            return response()->json(['mensaje' => 'El mentor no está disponible en ese horario.'], 409);
        }

        $sesion = Sesion::create([
            'mentor_id'     => $request->mentor_id,
            'aprendiz_id'   => $request->aprendiz_id,
            'fecha'         => $request->fecha,
            'hora_inicio'   => $request->hora_inicio,
            'hora_fin'      => $request->hora_fin,
            'estado'        => 'pendiente', // siempre pendiente al crear
            'observaciones' => $request->observaciones,
        ]);

        // El evento de Google Calendar se crea recién cuando el mentor
        // confirma la sesión (ver confirmar()), no al agendarla.

        // Avisar por email al mentor de que tiene una nueva solicitud
        $aprendiz = $request->attributes->get('usuario_auth');
        $mentor   = Usuario::find($sesion->mentor_id);

        if ($mentor) {
            try {
                Mail::send('emails.sesion_solicitada', [
                    'nombre'         => $mentor->nombre,
                    'nombre_aprendiz'=> $aprendiz->nombre,
                    'fecha'          => \Carbon\Carbon::parse($sesion->fecha)->format('d/m/Y'),
                    'hora_inicio'    => substr($sesion->hora_inicio, 0, 5),
                    'hora_fin'       => substr($sesion->hora_fin, 0, 5),
                    'observaciones'  => $sesion->observaciones,
                    'frontend_url'   => env('FRONTEND_URL', 'http://localhost:5173'),
                ], function ($msg) use ($mentor) {
                    $msg->to($mentor->email, $mentor->nombre)
                        ->subject('🔔 Nueva solicitud de mentoría — Plataforma Mentoría');
                });
            } catch (\Exception $e) {
                // El email falla silenciosamente — la sesión ya quedó creada
                \Log::error('Error enviando email de nueva solicitud de sesión: ' . $e->getMessage());
            }
        }

        return response()->json(['mensaje' => 'Sesión creada correctamente', 'sesion' => $sesion], 201);
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'mentor_id' => 'required|exists:usuarios,id',
                'aprendiz_id' => 'required|exists:usuarios,id',
                'fecha' => 'required|date',
                'hora_inicio' => 'required',
                'hora_fin' => 'required',
                'estado' => 'required|in:pendiente,confirmada,completada,cancelada',
                'observaciones' => 'nullable|string|max:500'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['mensaje' => 'Error de validación', 'errores' => $e->errors()], 422);
        }

        $sesion = Sesion::find($id);
        if (!$sesion) {
            return response()->json(['mensaje' => 'Sesión no encontrada'], 404);
        }

        $conflicto = Sesion::where('mentor_id', $request->mentor_id)
            ->where('fecha', $request->fecha)
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->where('id', '!=', $id)
            ->where(function ($query) use ($request) {
                $query->where('hora_inicio', '<', $request->hora_fin)
                      ->where('hora_fin', '>', $request->hora_inicio);
            })->exists();

        if ($conflicto) {
            return response()->json(['mensaje' => 'El mentor no está disponible en ese horario.'], 409);
        }

        $sesion->update([
            'mentor_id'    => $request->mentor_id,
            'aprendiz_id'  => $request->aprendiz_id,
            'fecha'        => $request->fecha,
            'hora_inicio'  => $request->hora_inicio,
            'hora_fin'     => $request->hora_fin,
            'estado'       => $request->estado,
            'observaciones'=> $request->observaciones,
        ]);

        // ── Google Calendar ───────────────────────────────────────────────
        // Solo sincroniza si la sesión ya tenía evento(s) creado(s) (estaba confirmada).
        $aprendiz = Usuario::find($sesion->aprendiz_id);
        $mentor   = Usuario::find($sesion->mentor_id);
        $calendar = new GoogleCalendarService();

        if ($sesion->google_calendar_event_id && $aprendiz?->google_refresh_token && $mentor) {
            $calendar->actualizarEvento(
                $sesion->google_calendar_event_id,
                $aprendiz->google_refresh_token,
                $sesion,
                $aprendiz->nombre,
                $mentor->nombre,
                $mentor->nombre,
                $mentor->email
            );
        }

        if ($sesion->google_calendar_event_id_mentor && $mentor?->google_refresh_token && $aprendiz) {
            $calendar->actualizarEvento(
                $sesion->google_calendar_event_id_mentor,
                $mentor->google_refresh_token,
                $sesion,
                $aprendiz->nombre,
                $mentor->nombre,
                $aprendiz->nombre,
                $aprendiz->email
            );
        }

        return response()->json(['mensaje' => 'Sesión actualizada correctamente', 'sesion' => $sesion]);
    }

    public function completar(Request $request, $id)
    {
        $sesion = Sesion::find($id);
        if (!$sesion) {
            return response()->json(['mensaje' => 'Sesión no encontrada'], 404);
        }

        $usuario = $request->attributes->get('usuario_auth');

        if ($sesion->mentor_id !== $usuario->id) {
            return response()->json(['mensaje' => 'Solo el mentor puede marcar la sesión como completada'], 403);
        }

        if ($sesion->estado !== 'confirmada') {
            return response()->json(['mensaje' => 'Solo se pueden completar sesiones confirmadas'], 422);
        }

        $sesion->estado = 'completada';
        $sesion->save();

        // Avisar por email al aprendiz de que ya puede valorar la sesión
        $aprendiz = Usuario::find($sesion->aprendiz_id);

        if ($aprendiz) {
            try {
                Mail::send('emails.sesion_completada', [
                    'nombre'        => $aprendiz->nombre,
                    'nombre_mentor' => $usuario->nombre,
                    'fecha'         => \Carbon\Carbon::parse($sesion->fecha)->format('d/m/Y'),
                    'hora_inicio'   => substr($sesion->hora_inicio, 0, 5),
                    'hora_fin'      => substr($sesion->hora_fin, 0, 5),
                    'frontend_url'  => env('FRONTEND_URL', 'http://localhost:5173'),
                ], function ($msg) use ($aprendiz) {
                    $msg->to($aprendiz->email, $aprendiz->nombre)
                        ->subject('🎓 Sesión completada — ¡Ya puedes valorarla!');
                });
            } catch (\Exception $e) {
                // El email falla silenciosamente — la sesión ya quedó completada
                \Log::error('Error enviando email de sesión completada: ' . $e->getMessage());
            }
        }

        return response()->json(['mensaje' => 'Sesión marcada como completada', 'sesion' => $sesion]);
    }

    public function confirmar(Request $request, $id)
    {
        $sesion = Sesion::find($id);
        if (!$sesion) {
            return response()->json(['mensaje' => 'Sesión no encontrada'], 404);
        }

        $usuario = $request->attributes->get('usuario_auth');

        if ($sesion->mentor_id !== $usuario->id) {
            return response()->json(['mensaje' => 'Solo el mentor puede confirmar esta sesión'], 403);
        }

        if ($sesion->estado !== 'pendiente') {
            return response()->json(['mensaje' => 'Solo se pueden confirmar sesiones pendientes'], 422);
        }

        try {
            $request->validate([
                'link_meet' => 'required|url',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['mensaje' => 'El link de Meet es requerido y debe ser una URL válida', 'errores' => $e->errors()], 422);
        }

        $sesion->estado    = 'confirmada';
        $sesion->link_meet = $request->link_meet;
        $sesion->save();

        $aprendiz = Usuario::find($sesion->aprendiz_id);
        $mentor   = Usuario::find($sesion->mentor_id);

        // ── Google Calendar: se crea el evento UNA sola vez (con el token de
        // quien lo tenga disponible) y se invita a la contraparte como asistente.
        // Google Calendar copia automáticamente el evento al calendario del
        // invitado, así que crearlo también desde el otro lado duplicaría el
        // evento (2 recordatorios) en ambos calendarios.
        $calendar = new GoogleCalendarService();

        if ($aprendiz?->google_refresh_token && $mentor) {
            $eventId = $calendar->crearEvento(
                $aprendiz->google_refresh_token,
                $sesion,
                $aprendiz->nombre,
                $mentor->nombre,
                $mentor->nombre,
                $mentor->email
            );
            if ($eventId) {
                $sesion->google_calendar_event_id = $eventId;
            }
        } elseif ($mentor?->google_refresh_token && $aprendiz) {
            $eventId = $calendar->crearEvento(
                $mentor->google_refresh_token,
                $sesion,
                $aprendiz->nombre,
                $mentor->nombre,
                $aprendiz->nombre,
                $aprendiz->email
            );
            if ($eventId) {
                $sesion->google_calendar_event_id_mentor = $eventId;
            }
        }

        if ($sesion->isDirty()) {
            $sesion->save();
        }

        // Enviar recordatorio por email al aprendiz y al mentor
        $fechaFormateada = \Carbon\Carbon::parse($sesion->fecha)->format('d/m/Y');
        $horaInicio      = substr($sesion->hora_inicio, 0, 5);
        $horaFin         = substr($sesion->hora_fin, 0, 5);

        $datosBase = [
            'fecha'         => $fechaFormateada,
            'hora_inicio'   => $horaInicio,
            'hora_fin'      => $horaFin,
            'link_meet'     => $sesion->link_meet,
            'observaciones' => $sesion->observaciones,
        ];

        try {
            // Email al aprendiz
            if ($aprendiz) {
                Mail::send('emails.sesion_confirmada', array_merge($datosBase, [
                    'nombre'       => $aprendiz->nombre,
                    'mensaje'      => 'Tu sesión de mentoría ha sido confirmada. Aquí tienes todos los detalles:',
                    'etiqueta_otro'=> 'Mentor',
                    'nombre_otro'  => $mentor->nombre ?? 'Tu mentor',
                ]), function ($msg) use ($aprendiz) {
                    $msg->to($aprendiz->email, $aprendiz->nombre)
                        ->subject('✅ Sesión confirmada — Plataforma Mentoría');
                });
            }

            // Email al mentor
            if ($mentor) {
                Mail::send('emails.sesion_confirmada', array_merge($datosBase, [
                    'nombre'       => $mentor->nombre,
                    'mensaje'      => 'Has confirmado una sesión de mentoría. Aquí tienes los detalles:',
                    'etiqueta_otro'=> 'Aprendiz',
                    'nombre_otro'  => $aprendiz->nombre ?? 'Tu aprendiz',
                ]), function ($msg) use ($mentor) {
                    $msg->to($mentor->email, $mentor->nombre)
                        ->subject('✅ Sesión confirmada — Plataforma Mentoría');
                });
            }
        } catch (\Exception $e) {
            // El email falla silenciosamente — la sesión ya quedó confirmada
            \Log::error('Error enviando email de confirmación de sesión: ' . $e->getMessage());
        }

        return response()->json(['mensaje' => 'Sesión confirmada correctamente', 'sesion' => $sesion]);
    }

    public function cancelar(Request $request, $id)
    {
        $sesion = Sesion::find($id);
        if (!$sesion) {
            return response()->json(['mensaje' => 'Sesión no encontrada'], 404);
        }

        $usuario = $request->attributes->get('usuario_auth');

        if ($sesion->aprendiz_id !== $usuario->id && $sesion->mentor_id !== $usuario->id) {
            return response()->json(['mensaje' => 'No tienes permiso para cancelar esta sesión'], 403);
        }

        try {
            $request->validate([
                'motivo' => 'required|string|max:500',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['mensaje' => 'El motivo de cancelación es requerido', 'errores' => $e->errors()], 422);
        }

        // ── Google Calendar ───────────────────────────────────────────────
        $calendar = new GoogleCalendarService();

        if ($sesion->google_calendar_event_id) {
            $aprendiz = Usuario::find($sesion->aprendiz_id);
            if ($aprendiz?->google_refresh_token) {
                $calendar->eliminarEvento(
                    $sesion->google_calendar_event_id,
                    $aprendiz->google_refresh_token
                );
            }
        }

        if ($sesion->google_calendar_event_id_mentor) {
            $mentor = Usuario::find($sesion->mentor_id);
            if ($mentor?->google_refresh_token) {
                $calendar->eliminarEvento(
                    $sesion->google_calendar_event_id_mentor,
                    $mentor->google_refresh_token
                );
            }
        }

        $sesion->estado             = 'cancelada';
        $sesion->motivo_cancelacion = $request->motivo;
        $sesion->save();

        // Enviar notificación por email al aprendiz y al mentor
        $aprendiz = Usuario::find($sesion->aprendiz_id);
        $mentor   = Usuario::find($sesion->mentor_id);
        $canceladoPorAprendiz = $sesion->aprendiz_id === $usuario->id;

        $fechaFormateada = \Carbon\Carbon::parse($sesion->fecha)->format('d/m/Y');
        $horaInicio      = substr($sesion->hora_inicio, 0, 5);
        $horaFin         = substr($sesion->hora_fin, 0, 5);

        $datosBase = [
            'fecha'         => $fechaFormateada,
            'hora_inicio'   => $horaInicio,
            'hora_fin'      => $horaFin,
            'observaciones' => $sesion->observaciones,
            'motivo'        => $sesion->motivo_cancelacion,
        ];

        try {
            // Email al aprendiz
            if ($aprendiz) {
                Mail::send('emails.sesion_cancelada', array_merge($datosBase, [
                    'nombre'       => $aprendiz->nombre,
                    'mensaje'      => $canceladoPorAprendiz
                        ? 'Has cancelado tu sesión de mentoría. Aquí tienes los detalles:'
                        : 'El mentor ha cancelado la sesión programada contigo. Aquí tienes los detalles:',
                    'etiqueta_otro'=> 'Mentor',
                    'nombre_otro'  => $mentor->nombre ?? 'Tu mentor',
                ]), function ($msg) use ($aprendiz) {
                    $msg->to($aprendiz->email, $aprendiz->nombre)
                        ->subject('❌ Sesión cancelada — Plataforma Mentoría');
                });
            }

            // Email al mentor
            if ($mentor) {
                Mail::send('emails.sesion_cancelada', array_merge($datosBase, [
                    'nombre'       => $mentor->nombre,
                    'mensaje'      => $canceladoPorAprendiz
                        ? 'El aprendiz ha cancelado la sesión programada contigo. Aquí tienes los detalles:'
                        : 'Has cancelado la sesión de mentoría. Aquí tienes los detalles:',
                    'etiqueta_otro'=> 'Aprendiz',
                    'nombre_otro'  => $aprendiz->nombre ?? 'Tu aprendiz',
                ]), function ($msg) use ($mentor) {
                    $msg->to($mentor->email, $mentor->nombre)
                        ->subject('❌ Sesión cancelada — Plataforma Mentoría');
                });
            }
        } catch (\Exception $e) {
            // El email falla silenciosamente — la sesión ya quedó cancelada
            \Log::error('Error enviando email de cancelación de sesión: ' . $e->getMessage());
        }

        return response()->json(['mensaje' => 'Sesión cancelada correctamente', 'sesion' => $sesion]);
    }
}