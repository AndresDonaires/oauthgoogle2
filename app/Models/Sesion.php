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
        'observaciones',
        'link_meet',
        'motivo_cancelacion',
        'google_calendar_event_id',
        'google_calendar_event_id_mentor',
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