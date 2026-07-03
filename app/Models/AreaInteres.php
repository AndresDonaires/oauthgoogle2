<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AreaInteres extends Model
{
    protected $table = 'areas_interes';

    protected $fillable = [
        'nombre',
        'estado',
    ];

    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_actualizacion';
}
