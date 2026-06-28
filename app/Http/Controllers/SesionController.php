<?php

namespace App\Http\Controllers;

use App\Models\Sesion;
use Illuminate\Http\Request;

class SesionController extends Controller
{
    public function index()
    {
        return response()->json(
            Sesion::all()
        );
    }

    public function show($id)
    {
        $sesion = Sesion::find($id);

        if (!$sesion) {
            return response()->json([
                'mensaje' => 'Sesión no encontrada'
            ], 404);
        }

        return response()->json($sesion);
    }

    public function store(Request $request)
    {


        $request->validate([
            'mentor_id' => 'required|exists:usuarios,id',
            'aprendiz_id' => 'required|exists:usuarios,id',
            'fecha' => 'required|date',
            'hora_inicio' => 'required',
            'hora_fin' => 'required',
            'estado' => 'required|in:pendiente,confirmada,completada,cancelada',
            'observaciones' => 'nullable|string|max:500'
        ]);

        $conflicto = Sesion::where('mentor_id', $request->mentor_id)
            ->where('fecha', $request->fecha)
            ->where('estado', '!=', 'cancelada')
            ->where(function ($query) use ($request) {
                $query->where('hora_inicio', '<', $request->hora_fin)
                      ->where('hora_fin', '>', $request->hora_inicio);
        })
        ->exists();

        if ($conflicto) {
            return response()->json([
                'mensaje' => 'El mentor no está disponible en ese horario.'
            ], 409);
        }        

        $sesion = Sesion::create([
            'mentor_id' => $request->mentor_id,
            'aprendiz_id' => $request->aprendiz_id,
            'fecha' => $request->fecha,
            'hora_inicio' => $request->hora_inicio,
            'hora_fin' => $request->hora_fin,
            'estado' => $request->estado,
            'observaciones' => $request->observaciones
        ]);

        return response()->json([
            'mensaje' => 'Sesión creada correctamente',
            'sesion' => $sesion
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'mentor_id' => 'required|exists:usuarios,id',
            'aprendiz_id' => 'required|exists:usuarios,id',
            'fecha' => 'required|date',
            'hora_inicio' => 'required',
            'hora_fin' => 'required',
            'estado' => 'required|in:pendiente,confirmada,completada,cancelada',
            'observaciones' => 'nullable|string|max:500'
        ]);

        $sesion = Sesion::find($id);

        if (!$sesion) {
            return response()->json([
                'mensaje' => 'Sesión no encontrada'
            ], 404);
        }

        $conflicto = Sesion::where('mentor_id', $request->mentor_id)
            ->where('fecha', $request->fecha)
            ->where('estado', '!=', 'cancelada')
            ->where('id', '!=', $id)
            ->where(function ($query) use ($request) {
                $query->where('hora_inicio', '<', $request->hora_fin)
                      ->where('hora_fin', '>', $request->hora_inicio);
        })
        ->exists();

        if ($conflicto) {
            return response()->json([
                'mensaje' => 'El mentor no está disponible en ese horario.'
            ], 409);
        }

        $sesion->update([
             'mentor_id' => $request->mentor_id,
            'aprendiz_id' => $request->aprendiz_id,
            'fecha' => $request->fecha,
            'hora_inicio' => $request->hora_inicio,
            'hora_fin' => $request->hora_fin,
            'estado' => $request->estado,
            'observaciones' => $request->observaciones
        ]);

        return response()->json([
            'mensaje' => 'Sesión actualizada correctamente',
            'sesion' => $sesion
        ]);
    }

    public function cancelar($id)
    {
        $sesion = Sesion::find($id);

        if (!$sesion) {
            return response()->json([
                'mensaje' => 'Sesión no encontrada'
            ], 404);
        }

        $sesion->estado = 'cancelada';
        $sesion->save();

        return response()->json([
            'mensaje' => 'Sesión cancelada correctamente',
            'sesion' => $sesion
        ]);
    }
}