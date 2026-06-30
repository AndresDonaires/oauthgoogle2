<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerificarRol
{
    public function handle(Request $request, Closure $next, int $rolRequerido)
    {
        $usuario = $request->attributes->get('usuario_auth');

        if (!$usuario || $usuario->rol != $rolRequerido) {
            return response()->json(['error' => 'No tienes permisos para esta acción'], 403);
        }

        return $next($request);
    }
}
