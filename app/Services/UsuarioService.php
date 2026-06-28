<?php

namespace App\Services;

use App\Repositories\UsuarioRepository;

class UsuarioService
{
    protected $repository;

    public function __construct(UsuarioRepository $repository)
    {
        $this->repository = $repository;
    }

    public function obtenerOCrearUsuario($googleUser)
    {
        $usuario = $this->repository
            ->buscarPorGoogleId($googleUser->id);

        if (!$usuario) {

            $usuario = $this->repository->crear([
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'google_id' => $googleUser->id,
                'avatar' => $googleUser->avatar
            ]);

        }

        return $usuario;
    }
}