<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditoriaBodega extends Model
{
    protected $table = 'auditorias_bodega';

    protected $fillable = [
        'fecha_auditoria',
        'realizada_por',
        'observaciones',
        'estado',
    ];

    protected $casts = [
        'fecha_auditoria' => 'date',
    ];

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleAuditoriaBodega::class, 'auditoria_id');
    }

    public function realizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'realizada_por');
    }
}
