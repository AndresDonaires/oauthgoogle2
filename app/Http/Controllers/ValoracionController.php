<?php

namespace App\Http\Controllers;

use App\Models\Valoracion;
use Illuminate\Http\Request;
use App\Models\Sesion;

class ValoracionController extends Controller
{
    // Listar todas las valoraciones
    public function index()
    {
        return response()->json(
            Valoracion::all()
        );
    }

    // Consultar una valoración por ID
    public function show($id)
    {
        $valoracion = Valoracion::find($id);

        if (!$valoracion) {
            return response()->json([
                'mensaje' => 'Valoración no encontrada'
            ], 404);
        }

        return response()->json($valoracion);
    }

    // Registrar una valoración
    public function store(Request $request)
    {
        try {
        $request->validate([
            'sesion_id' => 'required|exists:sesiones,id',
            'mentor_id' => 'required|exists:usuarios,id',
            'aprendiz_id' => 'required|exists:usuarios,id',
            'calificacion' => 'required|integer|min:1|max:5',
            'comentario' => 'nullable|string|max:500'
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        // Esto te dirá exactamente qué campo falla
        return response()->json([
            'mensaje' => 'Error de validación',
            'errores' => $e->errors()
        ], 422);
    }

        $sesion = Sesion::find($request->sesion_id);

        if (
            $sesion->mentor_id != $request->mentor_id ||
            $sesion->aprendiz_id != $request->aprendiz_id
        ) {
            return response()->json([
                'mensaje' => 'El mentor o el aprendiz no corresponden a la sesión seleccionada.'
            ], 422);
        }

        if (Valoracion::where('sesion_id', $request->sesion_id)->exists()) {
            return response()->json([
                'mensaje' => 'La sesión ya tiene una valoración registrada.'
            ], 409);
        }

        $valoracion = Valoracion::create([
            'sesion_id' => $request->sesion_id,
            'mentor_id' => $request->mentor_id,
            'aprendiz_id' => $request->aprendiz_id,
            'calificacion' => $request->calificacion,
            'comentario' => $request->comentario
        ]);

        return response()->json([
            'mensaje' => 'Valoración registrada correctamente',
            'valoracion' => $valoracion
        ], 201);
    }
}