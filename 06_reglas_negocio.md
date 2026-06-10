# Reglas de Negocio y Validaciones

---

## Reglas Globales

| # | Regla | Dónde Aplicar |
|---|---|---|
| RN-01 | Solo un `AnoLectivo` puede tener `activo = true` a la vez | Modelo `AnoLectivo::activar()`, validación en seeder |
| RN-02 | El NIE es el identificador único nacional del estudiante; no puede repetirse | Migración `unique`, validación en formulario |
| RN-03 | Un estudiante solo puede tener una matrícula `ACTIVA` por año lectivo | Unique constraint en `matriculas` |
| RN-04 | Un docente puede tener múltiples asignaciones, pero cada combinación materia+sección+año es única | Unique constraint en `asignaciones_docentes` |
| RN-05 | La nota final se calcula automáticamente; el usuario no la edita directamente | Campo calculado o sobreescrito en el guardado |
| RN-06 | El docente solo puede acceder a notas y asistencias de sus propias asignaciones | Verificar en `mount()` y en cada acción de escritura |
| RN-07 | Una salida/merma de inventario no puede superar el stock actual del lote | Validar en `RegistroMovimiento::guardar()` |
| RN-08 | Una auditoría cerrada no puede modificarse | Verificar estado antes de cualquier escritura |
| RN-09 | Las notas se registran por trimestre (1, 2 o 3) | Enum/tinyInteger con validación |
| RN-10 | Los campos de nota se validan entre 0.00 y 10.00 | Regla `between:0,10` con 2 decimales |

---

## Validaciones por Formulario

### Estudiante

```php
'nie'                 => 'required|string|max:20|unique:estudiantes,nie,{$this->estudianteId}',
'nombres'             => 'required|string|max:100',
'apellidos'           => 'required|string|max:100',
'fecha_nacimiento'    => 'required|date|before:today',
'genero'              => 'required|in:M,F',
'es_repitente'        => 'boolean',
'tiene_extraedad'     => 'boolean',
'pertenece_dai'       => 'boolean',
'actividad_economica' => 'required|in:No trabaja,Caña de azúcar,...',
'convivencia'         => 'required|in:Vive con la madre,...',
```

### Tutor

```php
'nombre'            => 'required|string|max:100',
'parentesco'        => 'required|string|max:50',
'nivel_academico'   => 'required|in:No sabe leer/escribir,Educación Básica,Educación Media,Educación Superior',
'situacion_laboral' => 'required|in:Empleado,Comerciante,Oficios varios,No trabaja',
'telefono'          => 'nullable|string|max:20',
```

### Matrícula

```php
'estudiante_id'    => 'required|exists:estudiantes,id',
'seccion_id'       => 'required|exists:secciones,id',
'tipo_inscripcion' => 'required|in:V,N,T',
'fecha_matricula'  => 'required|date',
// Verificar unicidad lógica antes de insertar
```

### Registro de Nota (por campo)

```php
'registros.*.act1'        => 'nullable|numeric|between:0,10',
'registros.*.act2'        => 'nullable|numeric|between:0,10',
// ... act3–act7, rev_cuaderno igual
'registros.*.prueba1'     => 'nullable|numeric|between:0,10',
'registros.*.prueba2'     => 'nullable|numeric|between:0,10',
'registros.*.observaciones' => 'nullable|string|max:500',
```

### Movimiento de Inventario

```php
'lote_id'         => 'required|exists:lotes_alimentos,id',
'tipo_movimiento' => 'required|in:entrada,salida,merma',
'cantidad'        => 'required|numeric|min:0.001',
'unidad'          => 'required|string|max:10',
'fecha'           => 'required|date|before_or_equal:today',
'observaciones'   => 'nullable|string|max:500',
// Validación adicional:
// Si tipo_movimiento IN ('salida','merma') => $cantidad <= $lote->stock_actual
```

### Personal

```php
'dui'           => 'required|string|size:9|unique:personal,dui,{$this->personalId}',
'nombres'       => 'required|string|max:100',
'apellidos'     => 'required|string|max:100',
'tipo'          => 'required|in:docente,administrativo,servicio',
'fecha_ingreso' => 'required|date',
'correo'        => 'nullable|email|unique:personal,correo,{$this->personalId}',
'telefono'      => 'nullable|string|max:20',
```

---

## Fórmula de Nota Final

Según el reglamento del MINED (ajustar coeficientes si cambia la normativa):

```
promedio_actividades = promedio(act1..act7, rev_cuaderno)   [solo los no nulos]
nota_final = (promedio_actividades × 0.35) + (prueba1 × 0.30) + (prueba2 × 0.35)
```

- Resultado redondeado a 2 decimales.
- El sistema muestra la nota en tiempo real mientras el docente ingresa los valores.
- La nota se considera como **aprobada** si `nota_final >= 5.00`.

---

## Fórmula de Informe de Rendimiento

```
aprobados = COUNT(nota_final >= 5.00) por género
reprobados = COUNT(nota_final < 5.00) por género
desertores = COUNT(matriculas con estado = 'RETIRADA' o 'TRASLADADA')
sobredad = COUNT(estudiantes con tiene_extraedad = true) en la sección
repitentes = COUNT(estudiantes con es_repitente = true) en la sección
```

---

## Cálculo de Stock de Lote

```
stock_actual = peso_total_kg
             + SUM(movimientos tipo='entrada')
             - SUM(movimientos tipo='salida' OR 'merma')
```

Calculado como accessor en el modelo `LoteAlimento`. No persiste en la BD para garantizar consistencia.

---

## Auditoría de Bodega

1. Al **iniciar** la auditoría: se registra el `stock_sistema` de cada lote en ese momento.
2. El encargado ingresa el `stock_fisico` contado manualmente.
3. El sistema calcula `diferencia = stock_fisico - stock_sistema`.
4. Una diferencia **negativa** indica pérdida no documentada (posible merma sin registrar).
5. Al **cerrar** la auditoría: el estado cambia a `cerrada` y ya no se puede editar.

---

## Reglas de Sesión

- `session.lifetime = 15` minutos.
- Livewire detecta sesión expirada y redirige a `/login` con mensaje informativo.
- En el layout, un script JS verifica la inactividad del usuario y hace logout preventivo.
