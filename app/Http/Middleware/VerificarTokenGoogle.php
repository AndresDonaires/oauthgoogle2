<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\Usuario;
use Exception;

class VerificarTokenGoogle
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Token de autenticación requerido'], 401);
        }

        try {
            $googleUser = Socialite::driver('google')->stateless()->userFromToken($token);
            $usuario    = Usuario::where('email', $googleUser->getEmail())->first();

            if (!$usuario) {
                return response()->json(['error' => 'Usuario no registrado en el sistema'], 401);
            }

            // Inyectar usuario en el request para que los controllers lo usen
            $request->attributes->set('usuario_auth', $usuario);

        } catch (Exception $e) {
            return response()->json(['error' => 'Token inválido o expirado'], 401);
        }

        return $next($request);
    }
}
