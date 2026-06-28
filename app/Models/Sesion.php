<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sesion extends Model
{
    protected $table = 'sesiones';

    protected $fillable = [
        'mentor_id',
        'aprendiz_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'estado',
        'observaciones'
    ];

    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = null;
}