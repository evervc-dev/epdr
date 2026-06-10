<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TutorFamiliar extends Model
{
    protected $table = 'tutores_familiares';

    protected $fillable = ['nombre', 'parentesco', 'nivel_academico', 'situacion_laboral', 'telefono'];

    public function estudiantes(): BelongsToMany
    {
        return $this->belongsToMany(Estudiante::class, 'estudiante_tutor')
            ->withPivot('es_contacto_principal');
    }
}
