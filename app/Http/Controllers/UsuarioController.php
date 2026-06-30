<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Sesion;
use App\Models\Valoracion;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    public function index()
    {
        return response()->json(Usuario::all());
    }

    public function estadisticas()
    {
        return response()->json([
            'usuarios' => [
                'total'        => Usuario::count(),
                'activos'      => Usuario::where('estado', 'activo')->count(),
                'inactivos'    => Usuario::where('estado', 'inactivo')->count(),
                'estudiantes'  => Usuario::where('rol', 1)->count(),
                'mentores'     => Usuario::where('rol', 2)->count(),
                'admins'       => Usuario::where('rol', 3)->count(),
            ],
            'sesiones' => [
                'total'        => Sesion::count(),
                'pendientes'   => Sesion::where('estado', 'pendiente')->count(),
                'confirmadas'  => Sesion::where('estado', 'confirmada')->count(),
                'completadas'  => Sesion::where('estado', 'completada')->count(),
                'canceladas'   => Sesion::where('estado', 'cancelada')->count(),
            ],
            'valoraciones' => [
                'total'        => Valoracion::count(),
                'promedio'     => round(Valoracion::avg('calificacion') ?? 0, 1),
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'rol'    => 'sometimes|integer|in:1,2,3',
            'estado' => 'sometimes|string|in:activo,inactivo',
        ]);

        $usuario = Usuario::find($id);

        if (!$usuario) {
            return response()->json(['mensaje' => 'Usuario no encontrado'], 404);
        }

        $usuario->update($request->only(['rol', 'estado']));

        return response()->json($usuario);
    }
}

