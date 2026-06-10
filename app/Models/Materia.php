<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Materia extends Model
{
    protected $fillable = ['nombre', 'codigo', 'grado_id'];

    public function grado(): BelongsTo
    {
        return $this->belongsTo(Grado::class);
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsignacionDocente::class);
    }
}
