<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Perfil extends Model
{
    protected $table = 'perfiles';

    protected $fillable = [
        'usuario_id',
        'bio',
        'carrera',
        'ciclo',
        'habilidades',
        'disponibilidad',
        'foto_url'
    ];

    const CREATED_AT = null;
    const UPDATED_AT = 'fecha_actualizacion';
}