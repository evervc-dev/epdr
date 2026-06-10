<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroNota extends Model
{
    protected $table = 'registro_notas';

    protected $fillable = [
        'matricula_id',
        'asignacion_docente_id',
        'trimestre',
        'act1',
        'act2',
        'act3',
        'act4',
        'act5',
        'act6',
        'act7',
        'rev_cuaderno',
        'prueba1',
        'prueba2',
        'nota_final',
        'observaciones',
    ];

    protected $casts = [
        'trimestre' => 'integer',
        'act1' => 'float',
        'act2' => 'float',
        'act3' => 'float',
        'act4' => 'float',
        'act5' => 'float',
        'act6' => 'float',
        'act7' => 'float',
        'rev_cuaderno' => 'float',
        'prueba1' => 'float',
        'prueba2' => 'float',
        'nota_final' => 'float',
    ];

    protected static function booted()
    {
        static::saving(function ($registroNota) {
            $registroNota->nota_final = $registroNota->calcularNotaFinal();
        });
    }

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class);
    }

    public function asignacionDocente(): BelongsTo
    {
        return $this->belongsTo(AsignacionDocente::class);
    }

    /**
     * Calcula la nota final ponderada del trimestre
     */
    public function calcularNotaFinal(): float
    {
        $actividades = collect([
            $this->act1,
            $this->act2,
            $this->act3,
            $this->act4,
            $this->act5,
            $this->act6,
            $this->act7,
            $this->rev_cuaderno,
        ])->filter(fn ($val) => ! is_null($val));

        $avgActividades = $actividades->isEmpty() ? 0 : $actividades->avg();

        return round(
            ($avgActividades * 0.35) + (($this->prueba1 ?? 0) * 0.30) + (($this->prueba2 ?? 0) * 0.35),
            2
        );
    }
}
