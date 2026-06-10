# Especificación de Base de Datos y Migraciones

---

## Convenciones de Nomenclatura

- Tablas en `snake_case` plural.
- Claves foráneas: `{tabla_singular}_id`.
- Campos booleanos: prefijo `es_`, `tiene_`, `pertenece_`.
- Timestamps estándar de Laravel: `created_at`, `updated_at`.
- Soft deletes donde se indique: `deleted_at`.

---

## Migraciones a Crear (en orden)

### 1. `anos_lectivos`

```php
Schema::create('anos_lectivos', function (Blueprint $table) {
    $table->id();
    $table->year('anio')->unique();
    $table->boolean('activo')->default(false);
    $table->date('fecha_inicio')->nullable();
    $table->date('fecha_fin')->nullable();
    $table->timestamps();
});
```

> Regla: Solo un registro puede tener `activo = true` a la vez. Validar en el modelo/servicio.

---

### 2. `grados`

```php
Schema::create('grados', function (Blueprint $table) {
    $table->id();
    $table->string('nombre'); // Ej: "1° Grado", "Parvularia 4 años"
    $table->enum('nivel', ['parvularia', 'basica']);
    $table->tinyInteger('orden')->unsigned(); // Para ordenamiento
    $table->timestamps();
});
```

**Datos semilla:** Parvularia 4, 5 y 6 años; 1° a 9° grado.

---

### 3. `secciones`

```php
Schema::create('secciones', function (Blueprint $table) {
    $table->id();
    $table->foreignId('grado_id')->constrained()->cascadeOnDelete();
    $table->foreignId('ano_lectivo_id')->constrained('anos_lectivos');
    $table->char('letra', 1); // A, B, C, D
    $table->string('turno')->nullable(); // Mañana / Tarde
    $table->timestamps();
});
```

---

### 4. `estudiantes`

```php
Schema::create('estudiantes', function (Blueprint $table) {
    $table->id();
    $table->string('nie', 20)->unique(); // Clave nacional del estudiante
    $table->string('nombres');
    $table->string('apellidos');
    $table->date('fecha_nacimiento');
    $table->enum('genero', ['M', 'F']);

    // Indicadores de vulnerabilidad académica
    $table->boolean('es_repitente')->default(false);
    $table->boolean('tiene_extraedad')->default(false);
    $table->boolean('pertenece_dai')->default(false);

    // Caracterización socioeconómica
    $table->enum('actividad_economica', [
        'No trabaja', 'Caña de azúcar', 'Pesca', 'Pepenador',
        'Trabajo doméstico', 'Cohetería', 'Café', 'Trabajos ambulantes',
        'Limpia autos/botas', 'Trabajos agrícolas', 'Otros'
    ])->default('No trabaja');

    $table->enum('convivencia', [
        'Vive con la madre', 'Vive con el padre', 'Vive con ambos',
        'Vive con familiares', 'No vive con familiares'
    ])->default('Vive con ambos');

    $table->softDeletes();
    $table->timestamps();
});
```

---

### 5. `tutores_familiares`

```php
Schema::create('tutores_familiares', function (Blueprint $table) {
    $table->id();
    $table->string('nombre');
    $table->string('parentesco'); // Madre, Padre, Abuelo/a, Tío/a, etc.
    $table->enum('nivel_academico', [
        'No sabe leer/escribir', 'Educación Básica',
        'Educación Media', 'Educación Superior'
    ]);
    $table->enum('situacion_laboral', [
        'Empleado', 'Comerciante', 'Oficios varios', 'No trabaja'
    ]);
    $table->string('telefono', 20)->nullable();
    $table->timestamps();
});
```

---

### 6. `estudiante_tutor` (tabla pivote N:M)

```php
Schema::create('estudiante_tutor', function (Blueprint $table) {
    $table->foreignId('estudiante_id')->constrained()->cascadeOnDelete();
    $table->foreignId('tutor_familiar_id')->constrained()->cascadeOnDelete();
    $table->boolean('es_contacto_principal')->default(false);
    $table->primary(['estudiante_id', 'tutor_familiar_id']);
});
```

---

### 7. `matriculas`

```php
Schema::create('matriculas', function (Blueprint $table) {
    $table->id();
    $table->foreignId('estudiante_id')->constrained();
    $table->foreignId('seccion_id')->constrained();
    $table->foreignId('ano_lectivo_id')->constrained('anos_lectivos');
    $table->enum('tipo_inscripcion', ['V', 'N', 'T'])
          ->comment('V=Variable, N=Nuevo, T=Traslado');
    $table->date('fecha_matricula');
    $table->enum('estado', ['ACTIVA', 'RETIRADA', 'TRASLADADA'])->default('ACTIVA');
    $table->timestamps();

    // Índice de rendimiento
    $table->index('estudiante_id', 'idx_matriculas_estudiante');
    $table->unique(['estudiante_id', 'seccion_id', 'ano_lectivo_id']);
});
```

---

### 8. `personal` (Recurso Humano)

```php
Schema::create('personal', function (Blueprint $table) {
    $table->id();
    $table->string('dui', 10)->unique();
    $table->string('nombres');
    $table->string('apellidos');
    $table->date('fecha_nacimiento');
    $table->enum('genero', ['M', 'F']);
    $table->string('telefono', 20)->nullable();
    $table->string('correo')->nullable();
    $table->enum('tipo', ['docente', 'administrativo', 'servicio']);
    $table->string('especialidad')->nullable(); // Para docentes
    $table->date('fecha_ingreso');
    $table->boolean('activo')->default(true);
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->softDeletes();
    $table->timestamps();
});
```

---

### 9. `materias`

```php
Schema::create('materias', function (Blueprint $table) {
    $table->id();
    $table->string('nombre');
    $table->string('codigo', 10)->unique();
    $table->foreignId('grado_id')->constrained();
    $table->timestamps();
});
```

---

### 10. `asignaciones_docentes`

```php
Schema::create('asignaciones_docentes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('personal_id')->constrained(); // Docente
    $table->foreignId('materia_id')->constrained();
    $table->foreignId('seccion_id')->constrained();
    $table->foreignId('ano_lectivo_id')->constrained('anos_lectivos');
    $table->timestamps();

    $table->unique(['personal_id', 'materia_id', 'seccion_id', 'ano_lectivo_id']);
});
```

---

### 11. `registro_notas`

```php
Schema::create('registro_notas', function (Blueprint $table) {
    $table->id();
    $table->foreignId('matricula_id')->constrained();
    $table->foreignId('asignacion_docente_id')->constrained('asignaciones_docentes');
    $table->tinyInteger('trimestre')->unsigned(); // 1, 2 o 3

    // Actividades (acumulado 35%)
    $table->decimal('act1', 4, 2)->nullable();
    $table->decimal('act2', 4, 2)->nullable();
    $table->decimal('act3', 4, 2)->nullable();
    $table->decimal('act4', 4, 2)->nullable();
    $table->decimal('act5', 4, 2)->nullable();
    $table->decimal('act6', 4, 2)->nullable();
    $table->decimal('act7', 4, 2)->nullable();
    $table->decimal('rev_cuaderno', 4, 2)->nullable();

    // Pruebas objetivas (30% y 35%)
    $table->decimal('prueba1', 4, 2)->nullable();
    $table->decimal('prueba2', 4, 2)->nullable();

    // Calculada por el sistema
    $table->decimal('nota_final', 4, 2)->nullable()->storedAs(
        // Fórmula ponderada — ajustar según reglamento del MINED
        '((act1+act2+act3+act4+act5+act6+act7+rev_cuaderno)/8 * 0.35) + (prueba1 * 0.30) + (prueba2 * 0.35)'
    );

    $table->text('observaciones')->nullable();
    $table->timestamps();

    $table->index('matricula_id', 'idx_notas_matricula');
    $table->unique(['matricula_id', 'asignacion_docente_id', 'trimestre']);
});
```

> **Nota:** Si la base de datos no soporta columnas generadas con la fórmula anterior, calcular `nota_final` en el modelo/servicio y persistirla manualmente.

---

### 12. `asistencias`

```php
Schema::create('asistencias', function (Blueprint $table) {
    $table->id();
    $table->foreignId('matricula_id')->constrained();
    $table->date('fecha');
    $table->enum('estado', ['P', 'A', 'J'])->comment('P=Presente, A=Ausente, J=Justificado');
    $table->text('observacion')->nullable();
    $table->foreignId('registrado_por')->constrained('users'); // Docente que registra
    $table->timestamps();

    $table->unique(['matricula_id', 'fecha']);
});
```

---

### 13. `productos` (Inventario)

```php
Schema::create('productos', function (Blueprint $table) {
    $table->id();
    $table->string('codigo', 20)->unique();
    $table->string('nombre');
    $table->enum('tipo_embalaje', ['Sacos', 'Cajas', 'Latas', 'Otro']);
    $table->string('unidad_peso')->default('kg'); // kg, lb, g
    $table->decimal('peso_por_unidad', 8, 3); // Kg por unidad
    $table->boolean('activo')->default(true);
    $table->timestamps();
});
```

---

### 14. `lotes_alimentos`

```php
Schema::create('lotes_alimentos', function (Blueprint $table) {
    $table->id();
    $table->string('codigo_lote', 30)->unique();
    $table->foreignId('producto_id')->constrained();
    $table->date('fecha_ingreso');
    $table->integer('cantidad_autorizada'); // Unidades autorizadas
    $table->integer('unidades_completas')->default(0);
    $table->decimal('unidades_fraccionadas', 8, 3)->default(0);
    $table->decimal('peso_total_kg', 10, 3)->default(0);
    $table->text('observaciones')->nullable();
    $table->timestamps();
});
```

---

### 15. `movimientos_inventario`

```php
Schema::create('movimientos_inventario', function (Blueprint $table) {
    $table->id();
    $table->foreignId('lote_id')->constrained('lotes_alimentos');
    $table->enum('tipo_movimiento', ['entrada', 'salida', 'merma']);
    $table->decimal('cantidad', 10, 3);
    $table->string('unidad')->default('kg');
    $table->date('fecha');
    $table->text('observaciones')->nullable();
    $table->foreignId('registrado_por')->constrained('users');
    $table->foreignId('auditoria_id')->nullable()->constrained('auditorias_bodega');
    $table->timestamps();
});
```

---

### 16. `auditorias_bodega`

```php
Schema::create('auditorias_bodega', function (Blueprint $table) {
    $table->id();
    $table->date('fecha_auditoria');
    $table->foreignId('realizada_por')->constrained('users');
    $table->text('observaciones')->nullable();
    $table->enum('estado', ['abierta', 'cerrada'])->default('abierta');
    $table->timestamps();
});
```

---

### 17. `detalle_auditorias_bodega`

```php
Schema::create('detalle_auditorias_bodega', function (Blueprint $table) {
    $table->id();
    $table->foreignId('auditoria_id')->constrained('auditorias_bodega')->cascadeOnDelete();
    $table->foreignId('lote_id')->constrained('lotes_alimentos');
    $table->decimal('stock_sistema', 10, 3);
    $table->decimal('stock_fisico', 10, 3);
    $table->decimal('diferencia', 10, 3)->storedAs('stock_fisico - stock_sistema');
    $table->text('observacion')->nullable();
    $table->timestamps();
});
```

---

### 18. `informes_rendimiento`

```php
Schema::create('informes_rendimiento', function (Blueprint $table) {
    $table->id();
    $table->foreignId('seccion_id')->constrained();
    $table->foreignId('ano_lectivo_id')->constrained('anos_lectivos');
    $table->tinyInteger('trimestre')->unsigned()->nullable(); // null = informe anual
    $table->integer('matricula_inicial')->default(0);
    $table->integer('matricula_actual')->default(0);
    $table->integer('aprobados_m')->default(0);
    $table->integer('aprobados_f')->default(0);
    $table->integer('reprobados_m')->default(0);
    $table->integer('reprobados_f')->default(0);
    $table->integer('desertores')->default(0);
    $table->integer('sobredad')->default(0);
    $table->integer('repitentes')->default(0);
    $table->text('observaciones')->nullable();
    $table->foreignId('generado_por')->constrained('users');
    $table->timestamps();
});
```

---

### 19. `reportes_estadisticos`

```php
Schema::create('reportes_estadisticos', function (Blueprint $table) {
    $table->id();
    $table->string('titulo');
    $table->json('parametros'); // Filtros usados para generar el reporte
    $table->string('tipo'); // caracterizacion, rendimiento, inventario, etc.
    $table->foreignId('generado_por')->constrained('users');
    $table->timestamp('generado_en')->useCurrent();
    $table->timestamps();
});
```

---

## Índices de Rendimiento

Agregar en las migraciones correspondientes:

```php
// En matriculas
$table->index(['ano_lectivo_id', 'seccion_id']);

// En registro_notas
$table->index(['asignacion_docente_id', 'trimestre']);

// En asistencias
$table->index(['matricula_id', 'fecha']);

// En movimientos_inventario
$table->index(['lote_id', 'tipo_movimiento', 'fecha']);
```

---

## Seeders Requeridos

| Seeder | Datos |
|---|---|
| `RolesPermisosSeeder` | Crear roles: admin, director, docente, bodega. Crear todos los permisos y asignarlos. |
| `AnoLectivoSeeder` | Año lectivo actual activo. |
| `GradosSeccionesSeeder` | Parvularia 4/5/6 años + 1° a 9° grado, secciones A–D por grado. |
| `AdminUserSeeder` | Usuario administrador inicial. |
| `MateriasSeeder` | Materias base por grado según currículo MINED. |
