<?php

namespace App\Http\Controllers;

use App\Models\Perfil;
use Illuminate\Http\Request;

class PerfilController extends Controller
{
    public function show($id)
    {
        $perfil = Perfil::where('usuario_id', $id)->first();

        if (!$perfil) {
            return response()->json([
                'mensaje' => 'Perfil no encontrado'
            ], 404);
        }

        return response()->json($perfil);
    }

    public function store(Request $request)
    {
        $request->validate([
            'usuario_id' => 'required|integer|exists:usuarios,id|unique:perfiles,usuario_id',
            'bio' => 'required|string|min:10|max:500',
            'carrera' => 'required|string|min:3|max:100',
            'ciclo' => 'required|integer|min:1|max:12',
            'habilidades' => 'nullable|string|max:500',
            'disponibilidad' => 'nullable|string|max:255',
            'foto_url' => 'nullable|url|max:255'
        ]);

        $perfil = Perfil::create([
            'usuario_id' => $request->usuario_id,
            'bio' => $request->bio,
            'carrera' => $request->carrera,
            'ciclo' => $request->ciclo,
            'habilidades' => $request->habilidades,
            'disponibilidad' => $request->disponibilidad,
            'foto_url' => $request->foto_url
        ]);

        return response()->json([
            'mensaje' => 'Perfil creado correctamente',
            'perfil' => $perfil
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'bio' => 'required|string|min:10|max:500',
            'carrera' => 'required|string|min:3|max:100',
            'ciclo' => 'required|integer|min:1|max:12',
            'habilidades' => 'nullable|string|max:500',
            'disponibilidad' => 'nullable|string|max:255',
            'foto_url' => 'nullable|url|max:255'
        ]);
    
        $perfil = Perfil::where('usuario_id', $id)->first();

        if (!$perfil) {
            return response()->json([
                'mensaje' => 'Perfil no encontrado'
            ], 404);
        }

        $perfil->update([
            'bio' => $request->bio,
            'carrera' => $request->carrera,
            'ciclo' => $request->ciclo,
            'habilidades' => $request->habilidades,
            'disponibilidad' => $request->disponibilidad,
            'foto_url' => $request->foto_url
        ]);

        return response()->json([
            'mensaje' => 'Perfil actualizado correctamente',
            'perfil' => $perfil
        ]);
    }

    public function index(Request $request)
    {
        // 1. Iniciamos la query cargando la relación 'usuario' para traer nombre y correo
        // Además, filtramos mediante whereHas para asegurar que el perfil pertenezca a un MENTOR activo
        $query = Perfil::with('usuario')
            ->withAvg('valoraciones', 'calificacion')
            ->whereHas('usuario', function ($q) {
                $q->where('rol', 2) // 1 = Estudiante, 2 = Mentor
                  ->where('estado', 'activo');
            });

        // 2. Lógica de Filtrado Dinámico (Criterios enviados desde el Frontend)
        if ($request->has('carrera') && !empty($request->carrera)) {
            $query->where('carrera', 'like', '%' . $request->carrera . '%');
        }

        if ($request->has('ciclo') && !empty($request->ciclo)) {
            $query->where('ciclo', $request->ciclo);
        }

        if ($request->has('habilidad') && !empty($request->habilidad)) {
            $query->where('habilidades', 'like', '%' . $request->habilidad . '%');
        }

        // 3. Lógica de Ordenamiento Dinámica
$ordenarPor = $request->get('ordenar_por', 'fecha_actualizacion'); 
$orden = $request->get('orden', 'desc'); 

$camposPermitidos = ['ciclo', 'carrera', 'fecha_actualizacion', 'usuario_id', 'valoraciones_avg_calificacion'];
$direccionesPermitidas = ['asc', 'desc'];

if (in_array($ordenarPor, $camposPermitidos) && in_array(strtolower($orden), $direccionesPermitidas)) {
    $query->orderBy($ordenarPor, $orden);
}

        // 4. Respuesta Estructurada y Paginada (5 elementos por página)
        $mentores = $query->paginate(5);

        return response()->json($mentores);
    }
}