<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleAuditoriaBodega extends Model
{
    protected $table = 'detalle_auditorias_bodega';

    protected $fillable = [
        'auditoria_id',
        'lote_id',
        'stock_sistema',
        'stock_fisico',
        'diferencia',
        'observacion',
    ];

    protected $casts = [
        'stock_sistema' => 'float',
        'stock_fisico' => 'float',
        'diferencia' => 'float',
    ];

    protected static function booted()
    {
        static::saving(function ($detalle) {
            $detalle->diferencia = ($detalle->stock_fisico ?? 0) - ($detalle->stock_sistema ?? 0);
        });
    }

    public function auditoria(): BelongsTo
    {
        return $this->belongsTo(AuditoriaBodega::class, 'auditoria_id');
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(LoteAlimento::class, 'lote_id');
    }
}
