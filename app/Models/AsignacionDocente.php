<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $personal_id
 * @property int $materia_id
 * @property int $seccion_id
 * @property int $ano_lectivo_id
 * @property Personal $personal
 * @property Materia $materia
 * @property Seccion $seccion
 * @property AnoLectivo $anoLectivo
 */
class AsignacionDocente extends Model
{
    protected $table = 'asignaciones_docentes';

    protected $fillable = ['personal_id', 'materia_id', 'seccion_id', 'ano_lectivo_id'];

    public function personal(): BelongsTo
    {
        return $this->belongsTo(Personal::class);
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class);
    }

    public function seccion(): BelongsTo
    {
        return $this->belongsTo(Seccion::class);
    }

    public function anoLectivo(): BelongsTo
    {
        return $this->belongsTo(AnoLectivo::class, 'ano_lectivo_id');
    }

    public function registroNotas(): HasMany
    {
        return $this->hasMany(RegistroNota::class, 'asignacion_docente_id');
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioClase::class, 'asignacion_docente_id');
    }

    public function scopeDelDocenteActual(Builder $query): Builder
    {
        return $query
            ->where('personal_id', auth()->user()->personal->id ?? 0)
            ->whereHas('seccion.anoLectivo', fn ($q) => $q->where('activo', true));
    }
}
