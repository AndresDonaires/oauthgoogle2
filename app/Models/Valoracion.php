<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Valoracion extends Model
{
    protected $table = 'valoraciones';

    protected $fillable = [
        'sesion_id',
        'mentor_id',
        'aprendiz_id',
        'calificacion',
        'comentario'
    ];

    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = null;

    public function aprendiz()
    {
        return $this->belongsTo(Usuario::class, 'aprendiz_id');
    }
}