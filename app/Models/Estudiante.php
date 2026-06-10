<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Estudiante extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nie', 'nombres', 'apellidos', 'fecha_nacimiento', 'genero',
        'es_repitente', 'tiene_extraedad', 'pertenece_dai',
        'actividad_economica', 'convivencia',
    ];

    protected $casts = [
        'es_repitente' => 'boolean',
        'tiene_extraedad' => 'boolean',
        'pertenece_dai' => 'boolean',
        'fecha_nacimiento' => 'date',
    ];

    public function tutores(): BelongsToMany
    {
        return $this->belongsToMany(TutorFamiliar::class, 'estudiante_tutor')
            ->withPivot('es_contacto_principal');
    }

    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class);
    }

    public function matriculaActiva(): HasOne
    {
        return $this->hasOne(Matricula::class)
            ->where('estado', 'ACTIVA')
            ->whereHas('seccion.anoLectivo', fn ($q) => $q->where('activo', true));
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombres} {$this->apellidos}";
    }
}
