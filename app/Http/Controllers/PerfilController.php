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

        $query = Perfil::query();

        if ($request->has('carrera')) {
            $query->where('carrera', 'like', '%' . $request->carrera . '%');
        }

        if ($request->has('ciclo')) {
            $query->where('ciclo', $request->ciclo);
        }

        $perfiles = $query->paginate(5);

        return response()->json($perfiles);
    }

}