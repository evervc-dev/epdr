<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $asignacion_docente_id
 * @property int $dia_semana
 * @property string $hora_inicio
 * @property string $hora_fin
 * @property AsignacionDocente $asignacionDocente
 */
class HorarioClase extends Model
{
    protected $table = 'horarios_clases';

    protected $fillable = [
        'asignacion_docente_id',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
    ];

    public function asignacionDocente(): BelongsTo
    {
        return $this->belongsTo(AsignacionDocente::class, 'asignacion_docente_id');
    }

    /**
     * Checks for schedule conflicts for a teacher or section.
     * Returns a string describing the conflict, or null if no conflict.
     */
    public static function hasCollision(int $diaSemana, string $horaInicio, string $horaFin, int $asignacionDocenteId, ?int $ignoreId = null): ?string
    {
        $asig = AsignacionDocente::findOrFail($asignacionDocenteId);

        // 1. Docente collision: same teacher, same day, overlapping times
        $docenteId = $asig->personal_id;
        $docenteConflict = self::where('dia_semana', $diaSemana)
            ->where('id', '!=', $ignoreId)
            ->whereHas('asignacionDocente', function ($q) use ($docenteId, $asig) {
                $q->where('personal_id', $docenteId)
                    ->where('ano_lectivo_id', $asig->ano_lectivo_id);
            })
            ->where(function ($q) use ($horaInicio, $horaFin) {
                $q->where('hora_inicio', '<', $horaFin)
                    ->where('hora_fin', '>', $horaInicio);
            })
            ->first();

        if ($docenteConflict) {
            $conflictAsig = $docenteConflict->asignacionDocente;

            return "Conflicto de Docente: El docente {$conflictAsig->personal->nombre_completo} ya tiene asignada la materia '{$conflictAsig->materia->nombre}' en la sección '{$conflictAsig->seccion->nombre_completo}' el mismo día de ".substr($docenteConflict->hora_inicio, 0, 5).' a '.substr($docenteConflict->hora_fin, 0, 5).'.';
        }

        // 2. Section collision: same section, same day, overlapping times
        $seccionId = $asig->seccion_id;
        $seccionConflict = self::where('dia_semana', $diaSemana)
            ->where('id', '!=', $ignoreId)
            ->whereHas('asignacionDocente', function ($q) use ($seccionId, $asig) {
                $q->where('seccion_id', $seccionId)
                    ->where('ano_lectivo_id', $asig->ano_lectivo_id);
            })
            ->where(function ($q) use ($horaInicio, $horaFin) {
                $q->where('hora_inicio', '<', $horaFin)
                    ->where('hora_fin', '>', $horaInicio);
            })
            ->first();

        if ($seccionConflict) {
            $conflictAsig = $seccionConflict->asignacionDocente;

            return "Conflicto de Sección: La sección '{$conflictAsig->seccion->nombre_completo}' ya tiene programada la materia '{$conflictAsig->materia->nombre}' el mismo día de ".substr($seccionConflict->hora_inicio, 0, 5).' a '.substr($seccionConflict->hora_fin, 0, 5).'.';
        }

        return null;
    }
}
