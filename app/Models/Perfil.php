<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Valoracion;

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

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function valoraciones()
{
    return $this->hasMany(Valoracion::class, 'mentor_id'); 
}
}