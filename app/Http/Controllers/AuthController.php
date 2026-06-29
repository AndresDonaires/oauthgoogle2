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
            ->scopes([
                'openid',
                'profile',
                'email'
            ])
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

            if (!$usuario) {
                $usuario = Usuario::create([
                    'nombre' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'rol' => 1,
                    'estado' => 'activo'
                ]);

                Perfil::create([
                    'usuario_id' => $usuario->id,
                    'bio' => 'Estudiante en la plataforma.',
                    'carrera' => 'Por definir',
                    'ciclo' => 1,
                    'foto_url' => $googleUser->getAvatar()
                ]);
            } else {
                if (empty($usuario->google_id)) {
                    $usuario->update([
                        'google_id' => $googleUser->getId()
                    ]);
                }
            }

            // Mantenemos esto por si alguna otra vista web local requiere la persistencia rápida
            Auth::login($usuario);

            return response()->json([
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'email' => $usuario->email,
                'rol' => $usuario->rol,
                'estado' => $usuario->estado,
                'accessToken' => $googleUser->token
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error al autenticar con Google',
                'detalles' => $e->getMessage()
            ], 500);
        }
    }

    // CORRECCIÓN AQUÍ: Recibimos el Request para extraer las cabeceras de la API
    public function usuario(Request $request)
    {
        try {
            // 1. Extraer el token Bearer que el Frontend envía en 'Authorization'
            $token = $request->bearerToken();

            if (!$token) {
                return response()->json([
                    'error' => 'No se proporcionó un token de autenticación'
                ], 401);
            }

            // 2. Preguntar a Google de manera stateless a quién le pertenece este token
            $googleUser = Socialite::driver('google')->stateless()->userFromToken($token);

            // 3. Buscar al usuario en la base de datos local usando el email verificado por Google
            $usuario = Usuario::where('email', $googleUser->getEmail())->first();

            if (!$usuario) {
                return response()->json([
                    'error' => 'Usuario no encontrado en el sistema'
                ], 44);
            }

            // 4. Retornar los datos limpios en formato JSON
            return response()->json([
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'email' => $usuario->email,
                'rol' => $usuario->rol,
                'estado' => $usuario->estado
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Token inválido o expirado',
                'detalles' => $e->getMessage()
            ], 401);
        }
    }
}