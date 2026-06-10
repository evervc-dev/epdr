# Especificación de Reportes

Todos los reportes son requeridos por el **MINED** en forma trimestral y/o anual.  
Los reportes se generan como **PDF descargable** o **tabla HTML exportable a PDF**.  
Librería recomendada: `barryvdh/laravel-dompdf` o `spatie/laravel-pdf`.

---

## R-01 — Caracterización Demográfica por Grado

**Ruta:** `/reportes/caracterizacion`  
**Permiso:** `reportes.caracterizacion`  
**Tabla principal:** `estudiantes`, `matriculas`  
**Parámetros:** Año lectivo, Grado (opcional — si vacío genera para toda la institución)

### Contenido del reporte

| Sección | Descripción |
|---|---|
| Encabezado | Nombre del centro escolar, año lectivo, grado/sección o "Institucional", fecha de generación |
| Tabla 1 | Distribución por actividad económica del estudiante |
| Tabla 2 | Distribución por tipo de convivencia familiar |
| Tabla 3 | Perfil académico de los tutores (nivel_academico) |
| Tabla 4 | Situación laboral de los tutores |
| Tabla 5 | Indicadores: total DAI, total repitentes, total extraedad |
| Totales | Total matriculados, desglose M/F |

---

## R-02 — Informe de Rendimiento Académico

**Ruta:** `/reportes/rendimiento`  
**Permiso:** `informes.rendimiento`  
**Tabla principal:** `informes_rendimiento`, `registro_notas`  
**Parámetros:** Año lectivo, Sección, Trimestre (1/2/3 o Anual)

### Contenido del reporte

| Campo | Descripción |
|---|---|
| Matrícula inicial | Total al inicio del período |
| Matrícula actual | Total activos al momento del cierre |
| Aprobados H | Hombres con nota_final >= 5.00 |
| Aprobados M | Mujeres con nota_final >= 5.00 |
| Reprobados H | Hombres con nota_final < 5.00 |
| Reprobados M | Mujeres con nota_final < 5.00 |
| Desertores | Retiros y traslados en el período |
| Sobredad | Estudiantes con tiene_extraedad = true |
| Repitentes | Estudiantes con es_repitente = true |
| Observaciones | Campo de texto libre para la dirección |

El reporte se genera **por sección** y luego un **consolidado institucional**.

---

## R-03 — Cuadro de Notas por Materia/Trimestre

**Ruta:** Generado desde `RegistroNotas` con botón "Exportar"  
**Permiso:** `notas.ver_propias` (docente propio), `notas.ver_todas` (admin/director)  
**Tabla principal:** `registro_notas`  
**Parámetros:** Asignación docente, Trimestre

### Contenido

Tabla con una fila por estudiante (ordenado alfabéticamente):

| Columna | Descripción |
|---|---|
| NIE | Identificador del estudiante |
| Nombre completo | Apellidos + Nombres |
| Act. 1–7 | Notas de actividades |
| Rev. Cuaderno | Nota de revisión de cuaderno |
| Prueba 1 (30%) | Nota de primera prueba objetiva |
| Prueba 2 (35%) | Nota de segunda prueba objetiva |
| Nota Final | Calculada por el sistema |
| Observaciones | Inasistencias, prórrogas |

---

## R-04 — Reporte de Inventario de Alimentos

**Ruta:** `/reportes/inventario`  
**Permiso:** `reportes.inventario`  
**Tablas:** `lotes_alimentos`, `movimientos_inventario`, `auditorias_bodega`  
**Parámetros:** Rango de fechas, Producto (opcional)

### Secciones

1. **Estado de lotes:** código, producto, fecha ingreso, stock sistema, stock físico (última auditoría).
2. **Movimientos en el período:** tipo, producto, lote, cantidad, fecha, responsable.
3. **Diferencias de auditoría:** lotes con diferencia ≠ 0, ordenados por diferencia descendente.

---

## R-05 — Reporte de Asistencias

**Ruta:** `/asistencias/reporte`  
**Permiso:** `reportes.asistencias`  
**Tabla:** `asistencias`, `matriculas`  
**Parámetros:** Sección, rango de fechas

### Contenido

Tabla por estudiante:

| Columna | Descripción |
|---|---|
| Nombre | Estudiante |
| Total días | Días en el rango seleccionado |
| Presentes (P) | Días asistidos |
| Ausentes (A) | Inasistencias injustificadas |
| Justificados (J) | Inasistencias justificadas |
| % Asistencia | `(P / total_dias) * 100` |

---

## R-06 — Reportes Estadísticos Configurables

**Ruta:** `/reportes/estadisticos`  
**Permiso:** `reportes.estadisticos`  
**Tabla:** `reportes_estadisticos`

El director puede generar reportes ad hoc seleccionando:
- Tipo: caracterización, rendimiento, asistencias, inventario
- Filtros: año lectivo, grado, sección, trimestre, rango de fechas
- Agrupación: por grado, por sección, por género, institucional

Cada reporte generado queda registrado en `reportes_estadisticos` con los parámetros usados, para auditoría y regeneración futura.

---

## Exportación PDF

Usar el layout de vista Blade `resources/views/reportes/layout-pdf.blade.php` con:

- Encabezado: logo del MINED + nombre del centro escolar.
- Pie de página: número de página, fecha de impresión, usuario que generó.
- Estilos básicos en CSS inline (compatible con DomPDF).
- Orientación: vertical para la mayoría; horizontal para el cuadro de notas.
