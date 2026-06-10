<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoteAlimento extends Model
{
    protected $table = 'lotes_alimentos';

    protected $fillable = [
        'codigo_lote',
        'producto_id',
        'fecha_ingreso',
        'cantidad_autorizada',
        'unidades_completas',
        'unidades_fraccionadas',
        'peso_total_kg',
        'observaciones',
    ];

    protected $casts = [
        'fecha_ingreso' => 'date',
        'cantidad_autorizada' => 'integer',
        'unidades_completas' => 'integer',
        'unidades_fraccionadas' => 'float',
        'peso_total_kg' => 'float',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'lote_id');
    }

    public function detallesAuditoria(): HasMany
    {
        return $this->hasMany(DetalleAuditoriaBodega::class, 'lote_id');
    }

    /**
     * Accessor: stock actual calculado desde los movimientos
     */
    public function getStockActualAttribute(): float
    {
        $entradas = $this->movimientos()->where('tipo_movimiento', 'entrada')->sum('cantidad');
        $salidas = $this->movimientos()->whereIn('tipo_movimiento', ['salida', 'merma'])->sum('cantidad');

        return $this->peso_total_kg + $entradas - $salidas;
    }
}
