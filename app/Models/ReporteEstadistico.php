<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReporteEstadistico extends Model
{
    protected $table = 'reportes_estadisticos';

    protected $fillable = [
        'titulo',
        'parametros',
        'tipo',
        'generado_por',
        'generado_en',
    ];

    protected $casts = [
        'parametros' => 'array',
        'generado_en' => 'datetime',
    ];

    public function generador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generado_por');
    }
}
