<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    protected $fillable = [
        'codigo',
        'nombre',
        'tipo_embalaje',
        'unidad_peso',
        'peso_por_unidad',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'peso_por_unidad' => 'float',
    ];

    public function lotes(): HasMany
    {
        return $this->hasMany(LoteAlimento::class);
    }
}
