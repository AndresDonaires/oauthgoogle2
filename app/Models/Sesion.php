<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Usuario;

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

    public function mentor()
    {
        return $this->belongsTo(Usuario::class, 'mentor_id');
    }

    public function aprendiz()
    {
        return $this->belongsTo(Usuario::class, 'aprendiz_id');
    }
}