# Orden de Implementación

Guía paso a paso para que Antigravity CLI desarrolle el sistema en el orden correcto,  
respetando las dependencias entre capas.

---

## Fase 1 — Base de Datos

Ejecutar en este orden para respetar las claves foráneas:

1. `anos_lectivos`
2. `grados`
3. `secciones` ← depende de `grados`, `anos_lectivos`
4. `estudiantes`
5. `tutores_familiares`
6. `estudiante_tutor` ← depende de `estudiantes`, `tutores_familiares`
7. `matriculas` ← depende de `estudiantes`, `secciones`, `anos_lectivos`
8. `personal` ← depende de `users`
9. `materias` ← depende de `grados`
10. `asignaciones_docentes` ← depende de `personal`, `materias`, `secciones`, `anos_lectivos`
11. `registro_notas` ← depende de `matriculas`, `asignaciones_docentes`
12. `asistencias` ← depende de `matriculas`, `users`
13. `productos`
14. `lotes_alimentos` ← depende de `productos`
15. `auditorias_bodega` ← depende de `users`
16. `movimientos_inventario` ← depende de `lotes_alimentos`, `users`, `auditorias_bodega`
17. `detalle_auditorias_bodega` ← depende de `auditorias_bodega`, `lotes_alimentos`
18. `informes_rendimiento` ← depende de `secciones`, `anos_lectivos`, `users`
19. `reportes_estadisticos` ← depende de `users`

---

## Fase 2 — Modelos

Crear en el mismo orden de la fase 1. Para cada modelo:

- [ ] Declarar `$fillable`
- [ ] Declarar `$casts`
- [ ] Definir relaciones (`hasMany`, `belongsTo`, `belongsToMany`)
- [ ] Agregar scopes si aplica
- [ ] Agregar accessors si aplica
- [ ] `use SoftDeletes` donde corresponda

---

## Fase 3 — Seeders

1. [ ] `RolesPermisosSeeder` — roles y permisos de Spatie
2. [ ] `AnoLectivoSeeder` — año lectivo activo
3. [ ] `GradosSeccionesSeeder` — grados 1–9 + parvularia + secciones A–D
4. [ ] `MateriasSeeder` — materias por grado
5. [ ] `AdminUserSeeder` — usuario admin inicial

---

## Fase 4 — Layout Principal

- [ ] Crear `resources/views/layouts/app.blade.php` con:
  - Sidebar con navegación dinámica por rol
  - Navbar superior
  - Área de contenido `{{ $slot }}`
  - Componente toast global
  - Script de inactividad (15 min)
- [ ] Crear componentes Blade reutilizables:
  - `x-modal-confirm`
  - `x-toast-notifications`
  - `x-tabla-paginada`
  - `x-badge-rol`

---

## Fase 5 — Módulo de Administración

- [ ] `Admin\GestionAnoLectivo`
- [ ] `Admin\GestionGradosSecciones`
- [ ] `Admin\GestionUsuarios`

---

## Fase 6 — Módulo de Caracterización Estudiantil

- [ ] `Estudiantes\ListadoEstudiantes`
- [ ] `Estudiantes\FormularioEstudiante`
- [ ] `Estudiantes\FormularioTutor`
- [ ] `Estudiantes\GestionMatriculas`

---

## Fase 7 — Módulo de Recurso Humano

- [ ] `Personal\ListadoPersonal`
- [ ] `Personal\FormularioPersonal`
- [ ] `Personal\AsignacionesDocente`

---

## Fase 8 — Módulo de Notas

- [ ] `Notas\SeleccionNotasDocente`
- [ ] `Notas\RegistroNotas`

---

## Fase 9 — Módulo de Asistencias

- [ ] `Asistencias\RegistroAsistencia`
- [ ] `Asistencias\ReporteAsistencias`

---

## Fase 10 — Módulo de Inventario

- [ ] `Inventario\ListadoProductos`
- [ ] `Inventario\GestionLotes`
- [ ] `Inventario\RegistroMovimiento`
- [ ] `Inventario\AuditoriaBodega`

---

## Fase 11 — Módulo de Reportes

- [ ] Layout PDF `resources/views/reportes/layout-pdf.blade.php`
- [ ] `Reportes\InformeRendimientoAcademico` + vista PDF
- [ ] `Reportes\CaracterizacionDemografica` + vista PDF
- [ ] `Reportes\ReporteInventario` + vista PDF
- [ ] `Reportes\GeneradorReporteEstadistico`

---

## Fase 12 — Dashboard

- [ ] `Dashboard` con tarjetas por rol

---

## Fase 13 — Rutas

- [ ] Registrar todas las rutas en `routes/web.php` con sus middleware de rol

---

## Fase 14 — Revisión Final

- [ ] Verificar que todos los permisos se comprueban en componentes
- [ ] Verificar que el docente no puede acceder a notas/asistencias de otros
- [ ] Verificar que solo un año lectivo puede estar activo
- [ ] Verificar que los índices de BD están creados
- [ ] Configurar `session.lifetime = 15` en `config/session.php`
- [ ] Configurar backup automático semanal

---

## Paquetes Adicionales Recomendados

| Paquete | Uso |
|---|---|
| `barryvdh/laravel-dompdf` | Generación de PDFs para reportes |
| `maatwebsite/excel` | Exportación a Excel (opcional) |

Instalar con:

```bash
composer require barryvdh/laravel-dompdf
```
