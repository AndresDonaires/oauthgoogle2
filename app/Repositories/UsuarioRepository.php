<?php

namespace App\Repositories;

use App\Models\User;

class UsuarioRepository
{
    public function buscarPorGoogleId($googleId)
    {
        return User::where('google_id', $googleId)->first();
    }

    public function crear(array $datos)
    {
        return User::create($datos);
    }
}