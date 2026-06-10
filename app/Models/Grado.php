<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grado extends Model
{
    protected $fillable = ['nombre', 'nivel', 'orden'];

    protected $casts = [
        'orden' => 'integer',
    ];

    public function secciones(): HasMany
    {
        return $this->hasMany(Seccion::class);
    }

    public function materias(): HasMany
    {
        return $this->hasMany(Materia::class);
    }
}
