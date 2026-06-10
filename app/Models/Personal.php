<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Personal extends Model
{
    use SoftDeletes;

    protected $table = 'personal';

    protected $fillable = [
        'dui', 'nombres', 'apellidos', 'fecha_nacimiento', 'genero',
        'telefono', 'correo', 'tipo', 'especialidad', 'fecha_ingreso', 'activo', 'user_id',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_ingreso' => 'date',
        'fecha_nacimiento' => 'date',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsignacionDocente::class);
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombres} {$this->apellidos}";
    }
}
