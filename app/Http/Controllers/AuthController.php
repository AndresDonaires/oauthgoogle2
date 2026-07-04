<?php

namespace App\Http\Controllers;

use Laravel\Socialite\Facades\Socialite;
use App\Models\Usuario;
use App\Models\Perfil; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Exception;

class AuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email', 'https://www.googleapis.com/auth/calendar.events'])
            ->with(['access_type' => 'offline', 'prompt' => 'consent'])
            ->stateless()
            ->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $usuario = Usuario::where(
                'email',
                $googleUser->getEmail()
            )->first();

            $isNuevo = false;

            if (!$usuario) {
                $isNuevo = true;
                $usuario = Usuario::create([
                    'nombre'               => $googleUser->getName(),
                    'email'                => $googleUser->getEmail(),
                    'google_id'            => $googleUser->getId(),
                    'google_refresh_token' => $googleUser->refreshToken,
                    'rol'                  => 1,
                    'estado'               => 'activo',
                ]);

                Perfil::create([
                    'usuario_id' => $usuario->id,
                    'bio'        => 'Estudiante en la plataforma.',
                    'carrera'    => 'Por definir',
                    'ciclo'      => 1,
                    'foto_url'   => $googleUser->getAvatar(),
                ]);
            } else {
                // Actualizar siempre el refresh_token (Google lo regenera en cada consent)
                $updates = [];
                if (empty($usuario->google_id))            $updates['google_id']            = $googleUser->getId();
                if (!empty($googleUser->refreshToken))     $updates['google_refresh_token'] = $googleUser->refreshToken;
                if (!empty($updates))                      $usuario->update($updates);
            }

            Auth::login($usuario);

            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
            $isNew = $isNuevo ? '&is_new=1' : '';

            return redirect()->away("{$frontendUrl}/auth/callback?token={$googleUser->token}{$isNew}");

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error al autenticar con Google',
                'detalles' => $e->getMessage()
            ], 500);
        }
    }

    public function usuario(Request $request)
    {
        // El middleware VerificarTokenGoogle ya validó el token e inyectó el usuario
        $usuario = $request->attributes->get('usuario_auth');

        return response()->json([
            'id'     => $usuario->id,
            'nombre' => $usuario->nombre,
            'email'  => $usuario->email,
            'rol'    => $usuario->rol,
            'estado' => $usuario->estado,
        ]);
    }

    public function actualizarRol(Request $request)
    {
        $request->validate(['rol' => 'required|in:1,2']);

        $usuario = $request->attributes->get('usuario_auth');
        $usuario->update(['rol' => $request->rol]);

        return response()->json([
            'id'     => $usuario->id,
            'nombre' => $usuario->nombre,
            'email'  => $usuario->email,
            'rol'    => $usuario->rol,
            'estado' => $usuario->estado,
        ]);
    }
}