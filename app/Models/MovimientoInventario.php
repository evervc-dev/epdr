<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoInventario extends Model
{
    protected $table = 'movimientos_inventario';

    protected $fillable = [
        'lote_id',
        'tipo_movimiento',
        'cantidad',
        'unidad',
        'fecha',
        'observaciones',
        'registrado_por',
        'auditoria_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'cantidad' => 'float',
    ];

    public function lote(): BelongsTo
    {
        return $this->belongsTo(LoteAlimento::class, 'lote_id');
    }

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function auditoria(): BelongsTo
    {
        return $this->belongsTo(AuditoriaBodega::class, 'auditoria_id');
    }
}
