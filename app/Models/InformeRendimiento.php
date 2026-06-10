<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InformeRendimiento extends Model
{
    protected $table = 'informes_rendimiento';

    protected $fillable = [
        'seccion_id',
        'ano_lectivo_id',
        'trimestre',
        'matricula_inicial',
        'matricula_actual',
        'aprobados_m',
        'aprobados_f',
        'reprobados_m',
        'reprobados_f',
        'desertores',
        'sobredad',
        'repitentes',
        'observaciones',
        'generado_por',
    ];

    protected $casts = [
        'trimestre' => 'integer',
        'matricula_inicial' => 'integer',
        'matricula_actual' => 'integer',
        'aprobados_m' => 'integer',
        'aprobados_f' => 'integer',
        'reprobados_m' => 'integer',
        'reprobados_f' => 'integer',
        'desertores' => 'integer',
        'sobredad' => 'integer',
        'repitentes' => 'integer',
    ];

    public function seccion(): BelongsTo
    {
        return $this->belongsTo(Seccion::class);
    }

    public function anoLectivo(): BelongsTo
    {
        return $this->belongsTo(AnoLectivo::class, 'ano_lectivo_id');
    }

    public function generador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generado_por');
    }
}
