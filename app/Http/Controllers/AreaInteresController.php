<?php

namespace App\Http\Controllers;

use App\Models\AreaInteres;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AreaInteresController extends Controller
{
    public function index(Request $request)
    {
        $usuario = $request->attributes->get('usuario_auth');
        $query   = AreaInteres::orderBy('nombre');

        // Solo el admin ve también las inactivas (para poder reactivarlas).
        // Mentor/aprendiz solo ven las activas, para elegirlas en su perfil.
        if (!$usuario || $usuario->rol !== 3) {
            $query->where('estado', 'activo');
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'nombre' => 'required|string|max:100|unique:areas_interes,nombre',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['mensaje' => 'Error de validación', 'errores' => $e->errors()], 422);
        }

        $area = AreaInteres::create([
            'nombre' => $request->nombre,
            'estado' => 'activo',
        ]);

        return response()->json([
            'mensaje' => 'Área de interés creada correctamente',
            'area'    => $area,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $area = AreaInteres::find($id);

        if (!$area) {
            return response()->json(['mensaje' => 'Área de interés no encontrada'], 404);
        }

        try {
            $request->validate([
                'nombre' => 'sometimes|required|string|max:100|unique:areas_interes,nombre,' . $area->id,
                'estado' => 'sometimes|required|string|in:activo,inactivo',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['mensaje' => 'Error de validación', 'errores' => $e->errors()], 422);
        }

        $area->update($request->only(['nombre', 'estado']));

        return response()->json([
            'mensaje' => 'Área de interés actualizada correctamente',
            'area'    => $area,
        ]);
    }
}
