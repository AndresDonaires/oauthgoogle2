<?php

namespace App\Http\Controllers;

use App\Models\Sesion;
use App\Models\Usuario;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SesionController extends Controller
{
    public function index(Request $request)
    {
        $usuario = $request->attributes->get('usuario_auth');

        $sesiones = Sesion::where('aprendiz_id', $usuario->id)
            ->orWhere('mentor_id', $usuario->id)
            ->get();

        return response()->json($sesiones);
    }

    public function show($id)
    {
        $sesion = Sesion::find($id);
        if (!$sesion) {
            return response()->json(['mensaje' => 'Sesión no encontrada'], 404);
        }
        return response()->json($sesion);
    }

    public function store(Request $request)
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

        $conflicto = Sesion::where('mentor_id', $request->mentor_id)
            ->where('fecha', $request->fecha)
            ->where('estado', '!=', 'cancelada')
            ->where(function ($query) use ($request) {
                $query->where('hora_inicio', '<', $request->hora_fin)
                      ->where('hora_fin', '>', $request->hora_inicio);
            })->exists();

        if ($conflicto) {
            return response()->json(['mensaje' => 'El mentor no está disponible en ese horario.'], 409);
        }        

        $sesion = Sesion::create([
            'mentor_id'    => $request->mentor_id,
            'aprendiz_id'  => $request->aprendiz_id,
            'fecha'        => $request->fecha,
            'hora_inicio'  => $request->hora_inicio,
            'hora_fin'     => $request->hora_fin,
            'estado'       => $request->estado,
            'observaciones'=> $request->observaciones,
        ]);

        // ── Google Calendarr ───────────────────────────────────────────────
        $aprendiz = $request->attributes->get('usuario_auth');
        $mentor   = Usuario::find($request->mentor_id);

        if ($aprendiz->google_refresh_token && $mentor) {
            $calendar  = new GoogleCalendarService();
            $eventId   = $calendar->crearEvento(
                $aprendiz->google_refresh_token,
                $sesion,
                $mentor->nombre,
                $mentor->email
            );
            if ($eventId) {
                $sesion->update(['google_calendar_event_id' => $eventId]);
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
            ->where('estado', '!=', 'cancelada')
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
        $aprendiz = Usuario::find($sesion->aprendiz_id);
        $mentor   = Usuario::find($sesion->mentor_id);

        if ($sesion->google_calendar_event_id && $aprendiz?->google_refresh_token && $mentor) {
            $calendar = new GoogleCalendarService();
            $calendar->actualizarEvento(
                $sesion->google_calendar_event_id,
                $aprendiz->google_refresh_token,
                $sesion,
                $mentor->nombre,
                $mentor->email
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

        $sesion->estado = 'confirmada';
        $sesion->save();

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

        // ── Google Calendar ───────────────────────────────────────────────
        if ($sesion->google_calendar_event_id) {
            $aprendiz = Usuario::find($sesion->aprendiz_id);
            if ($aprendiz?->google_refresh_token) {
                $calendar = new GoogleCalendarService();
                $calendar->eliminarEvento(
                    $sesion->google_calendar_event_id,
                    $aprendiz->google_refresh_token
                );
            }
        }

        $sesion->estado = 'cancelada';
        $sesion->save();

        return response()->json(['mensaje' => 'Sesión cancelada correctamente', 'sesion' => $sesion]);
    }
}