<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property Grado $grado
 */
class Seccion extends Model
{
    protected $table = 'secciones';

    protected $fillable = ['grado_id', 'ano_lectivo_id', 'letra', 'turno'];

    public function grado(): BelongsTo
    {
        return $this->belongsTo(Grado::class);
    }

    public function anoLectivo(): BelongsTo
    {
        return $this->belongsTo(AnoLectivo::class, 'ano_lectivo_id');
    }

    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class);
    }

    public function asignacionesDocentes(): HasMany
    {
        return $this->hasMany(AsignacionDocente::class);
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->grado->nombre} — Sección {$this->letra}";
    }
}
