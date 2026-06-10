<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class AnoLectivo extends Model
{
    protected $table = 'anos_lectivos';

    protected $fillable = ['anio', 'activo', 'fecha_inicio', 'fecha_fin'];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function secciones(): HasMany
    {
        return $this->hasMany(Seccion::class, 'ano_lectivo_id');
    }

    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class, 'ano_lectivo_id');
    }

    public function asignacionesDocentes(): HasMany
    {
        return $this->hasMany(AsignacionDocente::class, 'ano_lectivo_id');
    }

    public function scopeActivo(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public static function activar(int $id): void
    {
        DB::transaction(function () use ($id) {
            static::query()->update(['activo' => false]);
            static::findOrFail($id)->update(['activo' => true]);
        });
    }
}
