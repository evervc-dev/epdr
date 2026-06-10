# Componentes Livewire — Especificación por Módulo

Todos los componentes siguen la convención de Livewire 4.  
Namespace base: `App\Livewire\`  
Vistas base: `resources/views/livewire/`

---

## Convenciones Globales

- Cada módulo tiene su propio subdirectorio en `App\Livewire\{Modulo}\`.
- Los componentes de **tabla/listado** usan paginación de Laravel (`WithPagination`).
- Los componentes de **formulario** usan `#[Rule]` para validación inline.
- Los modales de confirmación usan un componente Blade reutilizable `<x-modal-confirm>`.
- La navegación usa un layout Blade `layouts/app.blade.php` con sidebar y navbar.
- Los mensajes de éxito/error usan `$this->dispatch('notify', ...)` hacia un componente de toast global.

---

## Layout Principal

**Archivo:** `resources/views/layouts/app.blade.php`

Debe contener:
- Sidebar con navegación por rol (mostrar solo los módulos permitidos según el rol del usuario).
- Navbar superior con nombre de usuario, rol activo y botón de cerrar sesión.
- Área de contenido principal (`{{ $slot }}`).
- Componente de notificaciones toast global.
- Script de cierre de sesión por inactividad (15 minutos).

---

## Módulo: Administración

### `Admin\GestionUsuarios`
**Ruta:** `/admin/usuarios`  
**Permiso:** `usuarios.ver`

Propiedades:
- `$search` — filtro por nombre/correo
- `$usuarios` — colección paginada
- `$modalAbierto`, `$usuarioId`, `$nombre`, `$correo`, `$password`, `$rol`

Métodos:
- `render()` — lista paginada con filtro
- `abrirModal($id = null)` — crear o editar
- `guardar()` — valida y persiste
- `confirmarEliminar($id)` — abre modal de confirmación
- `eliminar()` — elimina usuario

---

### `Admin\GestionAnoLectivo`
**Ruta:** `/admin/ano-lectivo`  
**Permiso:** `anio_lectivo.gestionar`

Propiedades:
- `$anos` — lista de años lectivos
- `$anio`, `$fechaInicio`, `$fechaFin`

Métodos:
- `activar($id)` — desactiva todos, activa el seleccionado (transacción DB)
- `crear()` — valida unicidad y guarda
- `render()`

---

### `Admin\GestionGradosSecciones`
**Ruta:** `/admin/grados-secciones`  
**Permiso:** `grados.gestionar`

Propiedades:
- `$grados`, `$gradoSeleccionado`, `$secciones`
- `$letra`, `$turno`

Métodos:
- `seleccionarGrado($id)`
- `agregarSeccion()` — crea sección para el año lectivo activo
- `eliminarSeccion($id)`

---

## Módulo: Caracterización Estudiantil

### `Estudiantes\ListadoEstudiantes`
**Ruta:** `/estudiantes`  
**Permiso:** `estudiantes.ver`

Propiedades:
- `$search` — NIE o nombre
- `$filtroGrado`, `$filtroSeccion`, `$filtroAnio`
- `$estudiantes` — paginado

Métodos:
- `render()` — con filtros aplicados
- `exportar()` — genera reporte de caracterización

---

### `Estudiantes\FormularioEstudiante`
**Ruta:** `/estudiantes/crear`, `/estudiantes/{id}/editar`  
**Permiso:** `estudiantes.crear` / `estudiantes.editar`

Propiedades (mapean directamente a la tabla `estudiantes`):
- `$nie`, `$nombres`, `$apellidos`, `$fechaNacimiento`, `$genero`
- `$esRepitente`, `$tieneExtraedad`, `$perteneceDAI`
- `$actividadEconomica`, `$convivencia`
- `$tutores` — array de tutores vinculados

Métodos:
- `mount($id = null)` — carga el estudiante si es edición
- `guardar()` — valida y persiste
- `agregarTutor()` — abre subformulario de tutor
- `desvincularTutor($tutorId)`

---

### `Estudiantes\FormularioTutor`
**Uso:** Subcomponente embebido o modal dentro de `FormularioEstudiante`.

Propiedades: `$nombre`, `$parentesco`, `$nivelAcademico`, `$situacionLaboral`, `$telefono`, `$esContactoPrincipal`

---

### `Estudiantes\GestionMatriculas`
**Ruta:** `/matriculas`  
**Permiso:** `matriculas.gestionar`

Propiedades:
- `$seccionId`, `$anioLectivoId`
- `$matriculas` — paginado por sección
- `$estudianteSearch`

Métodos:
- `matricular()` — asigna estudiante a sección
- `cambiarEstado($matriculaId, $estado)` — ACTIVA / RETIRADA / TRASLADADA

---

## Módulo: Gestión de Notas

### `Notas\SeleccionNotasDocente`
**Ruta:** `/notas`  
**Permiso:** `notas.ver_propias`

Propiedades:
- `$gradoId`, `$seccionId`, `$materiaId`, `$trimestreId`
- `$asignaciones` — asignaciones del docente autenticado (o todas si es admin/director)

Métodos:
- `seleccionar()` — redirige a `RegistroNotas` con los parámetros

---

### `Notas\RegistroNotas`
**Ruta:** `/notas/registro/{asignacion}/{trimestre}`  
**Permiso:** `notas.registrar`

Propiedades:
- `$asignacion` — modelo `AsignacionDocente`
- `$trimestre`
- `$registros` — array indexado por `matricula_id` con todos los campos de nota
- `$dirty` — IDs modificados pendientes de guardar

Métodos:
- `mount($asignacionId, $trimestre)` — carga matriculados y notas existentes
- `actualizarNota($matriculaId, $campo, $valor)` — actualiza `$registros` en memoria y marca `$dirty`
- `guardar()` — persiste todos los registros modificados, calcula `nota_final`
- `calcularNota($matriculaId)` — retorna la nota ponderada sin persistir (para previsualización)

> La lista de estudiantes se ordena **alfabéticamente** por apellido + nombre.  
> Todos los campos de nota se validan entre `0.00` y `10.00`.

---

## Módulo: Asistencias

### `Asistencias\RegistroAsistencia`
**Ruta:** `/asistencias`  
**Permiso:** `asistencias.registrar`

Propiedades:
- `$seccionId`, `$fecha`
- `$estudiantes` — array con `matricula_id`, `nombre`, `estado`, `observacion`

Métodos:
- `mount()` — carga la fecha actual y la sección por defecto del docente
- `cargarEstudiantes()` — reactivo al cambiar sección/fecha
- `guardar()` — upsert masivo en `asistencias`
- `marcarTodos($estado)` — helper para "todos presentes"

---

### `Asistencias\ReporteAsistencias`
**Ruta:** `/asistencias/reporte`  
**Permiso:** `reportes.asistencias`

Propiedades:
- `$seccionId`, `$fechaDesde`, `$fechaHasta`
- `$resumen` — array con totales P/A/J por estudiante

Métodos:
- `generar()` — calcula resumen
- `exportar()` — genera PDF o Excel

---

## Módulo: Inventario de Alimentos

### `Inventario\ListadoProductos`
**Ruta:** `/inventario/productos`  
**Permiso:** `inventario.productos`

Propiedades: `$search`, `$productos` (paginado)

Métodos: `render()`, `abrirModal($id = null)`, `guardar()`, `toggleActivo($id)`

---

### `Inventario\GestionLotes`
**Ruta:** `/inventario/lotes`  
**Permiso:** `inventario.lotes`

Propiedades:
- `$productoId`, `$codigoLote`, `$fechaIngreso`
- `$cantidadAutorizada`, `$unidadesCompletas`, `$unidadesFraccionadas`, `$pesoTotalKg`
- `$lotes` — paginado con stock calculado

Métodos:
- `guardar()` — crea lote nuevo
- `verMovimientos($loteId)` — muestra historial del lote

---

### `Inventario\RegistroMovimiento`
**Ruta:** `/inventario/movimientos`  
**Permiso:** `inventario.movimientos`

Propiedades:
- `$loteId`, `$tipoMovimiento` (entrada/salida/merma)
- `$cantidad`, `$unidad`, `$fecha`, `$observaciones`

Métodos:
- `guardar()` — valida stock suficiente para salidas/mermas, persiste y actualiza stock del lote

---

### `Inventario\AuditoriaBodega`
**Ruta:** `/inventario/auditoria`  
**Permiso:** `inventario.auditorias`

Propiedades:
- `$auditoriaId`, `$fecha`, `$estado`
- `$detalles` — array con `lote_id`, `stock_sistema`, `stock_fisico`, `diferencia`

Métodos:
- `iniciarAuditoria()` — crea registro en `auditorias_bodega`, carga todos los lotes con stock actual
- `guardarDetalle($loteId, $stockFisico)` — actualiza diferencia
- `cerrarAuditoria()` — cambia estado a `cerrada`, ya no permite modificaciones

---

## Módulo: Recurso Humano

### `Personal\ListadoPersonal`
**Ruta:** `/personal`  
**Permiso:** `personal.ver`

Propiedades: `$search`, `$filtroTipo` (docente/administrativo/servicio), `$personal` (paginado)

---

### `Personal\FormularioPersonal`
**Ruta:** `/personal/crear`, `/personal/{id}/editar`  
**Permiso:** `personal.crear` / `personal.editar`

Propiedades: mapean a tabla `personal`.  
Incluye: asignación opcional de cuenta de usuario del sistema.

---

### `Personal\AsignacionesDocente`
**Ruta:** `/personal/{id}/asignaciones`  
**Permiso:** `asignaciones.gestionar`

Propiedades:
- `$personalId`, `$materiaId`, `$seccionId`, `$anioLectivoId`
- `$asignaciones` — lista de asignaciones activas del docente

Métodos:
- `asignar()` — valida unicidad y crea registro
- `eliminar($id)` — elimina asignación

---

## Módulo: Informes y Reportes

### `Reportes\InformeRendimientoAcademico`
**Ruta:** `/reportes/rendimiento`  
**Permiso:** `informes.rendimiento`

Propiedades:
- `$seccionId`, `$anioLectivoId`, `$trimestre` (null = anual)
- `$informe` — datos calculados

Métodos:
- `generar()` — calcula y persiste en `informes_rendimiento`
- `exportarPDF()` — genera PDF con formato MINED

---

### `Reportes\CaracterizacionDemografica`
**Ruta:** `/reportes/caracterizacion`  
**Permiso:** `reportes.caracterizacion`

Propiedades:
- `$gradoId`, `$anioLectivoId`
- `$datos` — tablas cruzadas de variables socioeconómicas

Métodos:
- `generar()` — consulta con joins a `estudiantes`, `matriculas`, `tutores_familiares`
- `exportarPDF()`

---

### `Reportes\ReporteInventario`
**Ruta:** `/reportes/inventario`  
**Permiso:** `reportes.inventario`

Propiedades: `$fechaDesde`, `$fechaHasta`, `$productoId`

Métodos: `generar()`, `exportarPDF()`

---

### `Reportes\GeneradorReporteEstadistico`
**Ruta:** `/reportes/estadisticos`  
**Permiso:** `reportes.estadisticos`

Propiedades:
- `$tipo`, `$parametros` (array dinámico según tipo)
- `$historial` — reportes generados anteriormente

Métodos:
- `configurar()` — muestra campos de filtro según `$tipo`
- `generar()` — ejecuta la consulta, persiste en `reportes_estadisticos`
