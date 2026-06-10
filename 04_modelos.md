# Modelos Eloquent y Relaciones

Namespace base: `App\Models\`

---

## Convenciones

- Todos los modelos extienden `Illuminate\Database\Eloquent\Model`.
- Los que lo requieran usan `SoftDeletes`.
- Los timestamps están habilitados por defecto.
- Los `$fillable` deben declararse explícitamente en cada modelo.
- Los `$casts` se usan para booleanos, enums, JSON y fechas.

---

## `AnoLectivo`

```php
protected $table = 'anos_lectivos';
protected $fillable = ['anio', 'activo', 'fecha_inicio', 'fecha_fin'];
protected $casts = ['activo' => 'boolean', 'fecha_inicio' => 'date', 'fecha_fin' => 'date'];

// Relaciones
public function secciones(): HasMany { ... }
public function matriculas(): HasMany { ... }
public function asignacionesDocentes(): HasMany { ... }

// Scope
public function scopeActivo(Builder $query) { return $query->where('activo', true); }

// Método de negocio
public static function activar(int $id): void
{
    // Desactiva todos, activa el indicado — en una transacción
    DB::transaction(function () use ($id) {
        static::query()->update(['activo' => false]);
        static::findOrFail($id)->update(['activo' => true]);
    });
}
```

---

## `Grado`

```php
protected $fillable = ['nombre', 'nivel', 'orden'];
protected $casts = ['orden' => 'integer'];

public function secciones(): HasMany { ... }
public function materias(): HasMany { ... }
```

---

## `Seccion`

```php
protected $fillable = ['grado_id', 'ano_lectivo_id', 'letra', 'turno'];

public function grado(): BelongsTo { ... }
public function anoLectivo(): BelongsTo { ... }
public function matriculas(): HasMany { ... }
public function asignacionesDocentes(): HasMany { ... }

// Accessor para nombre completo
public function getNombreCompletoAttribute(): string
{
    return "{$this->grado->nombre} — Sección {$this->letra}";
}
```

---

## `Estudiante`

```php
use SoftDeletes;

protected $fillable = [
    'nie', 'nombres', 'apellidos', 'fecha_nacimiento', 'genero',
    'es_repitente', 'tiene_extraedad', 'pertenece_dai',
    'actividad_economica', 'convivencia',
];
protected $casts = [
    'es_repitente' => 'boolean',
    'tiene_extraedad' => 'boolean',
    'pertenece_dai' => 'boolean',
    'fecha_nacimiento' => 'date',
];

public function tutores(): BelongsToMany
{
    return $this->belongsToMany(TutorFamiliar::class, 'estudiante_tutor')
                ->withPivot('es_contacto_principal')
                ->withTimestamps();
}

public function matriculas(): HasMany { ... }

public function matriculaActiva(): HasOne
{
    return $this->hasOne(Matricula::class)
                ->where('estado', 'ACTIVA')
                ->whereHas('seccion.anoLectivo', fn($q) => $q->where('activo', true));
}

// Accessor
public function getNombreCompletoAttribute(): string
{
    return "{$this->nombres} {$this->apellidos}";
}
```

---

## `TutorFamiliar`

```php
protected $table = 'tutores_familiares';
protected $fillable = ['nombre', 'parentesco', 'nivel_academico', 'situacion_laboral', 'telefono'];

public function estudiantes(): BelongsToMany { ... }
```

---

## `Matricula`

```php
protected $fillable = [
    'estudiante_id', 'seccion_id', 'ano_lectivo_id',
    'tipo_inscripcion', 'fecha_matricula', 'estado',
];
protected $casts = ['fecha_matricula' => 'date'];

public function estudiante(): BelongsTo { ... }
public function seccion(): BelongsTo { ... }
public function anoLectivo(): BelongsTo { ... }
public function registroNotas(): HasMany { ... }
public function asistencias(): HasMany { ... }
```

---

## `Personal`

```php
use SoftDeletes;

protected $fillable = [
    'dui', 'nombres', 'apellidos', 'fecha_nacimiento', 'genero',
    'telefono', 'correo', 'tipo', 'especialidad', 'fecha_ingreso', 'activo', 'user_id',
];
protected $casts = ['activo' => 'boolean', 'fecha_ingreso' => 'date', 'fecha_nacimiento' => 'date'];

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
```

---

## `Materia`

```php
protected $fillable = ['nombre', 'codigo', 'grado_id'];

public function grado(): BelongsTo { ... }
public function asignaciones(): HasMany { ... }
```

---

## `AsignacionDocente`

```php
protected $table = 'asignaciones_docentes';
protected $fillable = ['personal_id', 'materia_id', 'seccion_id', 'ano_lectivo_id'];

public function personal(): BelongsTo { ... }
public function materia(): BelongsTo { ... }
public function seccion(): BelongsTo { ... }
public function anoLectivo(): BelongsTo { ... }
public function registroNotas(): HasMany { ... }

// Scope: asignaciones del docente autenticado en el año activo
public function scopeDelDocenteActual(Builder $query): Builder
{
    return $query
        ->where('personal_id', auth()->user()->personal->id ?? 0)
        ->whereHas('seccion.anoLectivo', fn($q) => $q->where('activo', true));
}
```

---

## `RegistroNota`

```php
protected $table = 'registro_notas';
protected $fillable = [
    'matricula_id', 'asignacion_docente_id', 'trimestre',
    'act1','act2','act3','act4','act5','act6','act7',
    'rev_cuaderno', 'prueba1', 'prueba2',
    'nota_final', 'observaciones',
];
protected $casts = [
    'act1' => 'float', 'act2' => 'float', 'act3' => 'float',
    'act4' => 'float', 'act5' => 'float', 'act6' => 'float',
    'act7' => 'float', 'rev_cuaderno' => 'float',
    'prueba1' => 'float', 'prueba2' => 'float', 'nota_final' => 'float',
];

public function matricula(): BelongsTo { ... }
public function asignacionDocente(): BelongsTo { ... }

// Método de cálculo de nota final
public function calcularNotaFinal(): float
{
    $actividades = collect([
        $this->act1, $this->act2, $this->act3, $this->act4,
        $this->act5, $this->act6, $this->act7, $this->rev_cuaderno,
    ])->filter()->avg() ?? 0;

    return round(
        ($actividades * 0.35) + (($this->prueba1 ?? 0) * 0.30) + (($this->prueba2 ?? 0) * 0.35),
        2
    );
}
```

---

## `Asistencia`

```php
protected $fillable = ['matricula_id', 'fecha', 'estado', 'observacion', 'registrado_por'];
protected $casts = ['fecha' => 'date'];

public function matricula(): BelongsTo { ... }
public function registrador(): BelongsTo
{
    return $this->belongsTo(User::class, 'registrado_por');
}
```

---

## `Producto`

```php
protected $fillable = ['codigo', 'nombre', 'tipo_embalaje', 'unidad_peso', 'peso_por_unidad', 'activo'];
protected $casts = ['activo' => 'boolean', 'peso_por_unidad' => 'float'];

public function lotes(): HasMany { ... }
```

---

## `LoteAlimento`

```php
protected $table = 'lotes_alimentos';
protected $fillable = [
    'codigo_lote', 'producto_id', 'fecha_ingreso',
    'cantidad_autorizada', 'unidades_completas', 'unidades_fraccionadas',
    'peso_total_kg', 'observaciones',
];
protected $casts = ['fecha_ingreso' => 'date', 'peso_total_kg' => 'float'];

public function producto(): BelongsTo { ... }
public function movimientos(): HasMany { ... }
public function detallesAuditoria(): HasMany { ... }

// Accessor: stock actual calculado desde movimientos
public function getStockActualAttribute(): float
{
    $entradas = $this->movimientos()->where('tipo_movimiento', 'entrada')->sum('cantidad');
    $salidas  = $this->movimientos()->whereIn('tipo_movimiento', ['salida', 'merma'])->sum('cantidad');
    return $this->peso_total_kg + $entradas - $salidas;
}
```

---

## `MovimientoInventario`

```php
protected $table = 'movimientos_inventario';
protected $fillable = [
    'lote_id', 'tipo_movimiento', 'cantidad', 'unidad',
    'fecha', 'observaciones', 'registrado_por', 'auditoria_id',
];
protected $casts = ['fecha' => 'date', 'cantidad' => 'float'];

public function lote(): BelongsTo { ... }
public function registrador(): BelongsTo { ... }
public function auditoria(): BelongsTo { ... }
```

---

## `AuditoriaBodega`

```php
protected $table = 'auditorias_bodega';
protected $fillable = ['fecha_auditoria', 'realizada_por', 'observaciones', 'estado'];
protected $casts = ['fecha_auditoria' => 'date'];

public function detalles(): HasMany
{
    return $this->hasMany(DetalleAuditoriaBodega::class, 'auditoria_id');
}
public function realizador(): BelongsTo { ... }
```

---

## `DetalleAuditoriaBodega`

```php
protected $table = 'detalle_auditorias_bodega';
protected $fillable = ['auditoria_id', 'lote_id', 'stock_sistema', 'stock_fisico', 'diferencia', 'observacion'];
protected $casts = ['stock_sistema' => 'float', 'stock_fisico' => 'float', 'diferencia' => 'float'];

public function auditoria(): BelongsTo { ... }
public function lote(): BelongsTo { ... }
```

---

## `InformeRendimiento`

```php
protected $table = 'informes_rendimiento';
protected $fillable = [
    'seccion_id', 'ano_lectivo_id', 'trimestre',
    'matricula_inicial', 'matricula_actual',
    'aprobados_m', 'aprobados_f', 'reprobados_m', 'reprobados_f',
    'desertores', 'sobredad', 'repitentes', 'observaciones', 'generado_por',
];

public function seccion(): BelongsTo { ... }
public function anoLectivo(): BelongsTo { ... }
public function generador(): BelongsTo { ... }
```

---

## `User` (extender el existente)

Agregar al modelo `User` de Laravel:

```php
use HasRoles; // Spatie

public function personal(): HasOne
{
    return $this->hasOne(Personal::class);
}
```
