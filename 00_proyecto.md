# SIG — Sistema de Información Gerencial  
## Centro Escolar "Pablo J. Aguirre"

---

## Descripción General

Sistema web para la gestión académica, administrativa y de recursos del Centro Escolar "Pablo J. Aguirre". Permite automatizar el control de notas, asistencias, caracterización estudiantil, recurso humano y el inventario del programa de alimentación escolar.

---

## Stack Tecnológico

| Componente | Tecnología |
|---|---|
| Framework | Laravel (versión actual del proyecto) |
| Frontend | Livewire 4 |
| Autenticación y roles | Spatie Laravel Permission (RBAC) |
| Base de datos | MySQL / MariaDB |
| Estilos | Tailwind CSS |

---

## Estado Inicial del Proyecto

- Proyecto Laravel creado y funcional.
- Spatie Laravel Permission instalado y configurado.
- Livewire 4 instalado.
- Componente Livewire de login funcional con su layout base.
- **No** existen migraciones de dominio, modelos ni componentes adicionales aún.

---

## Módulos del Sistema

| # | Módulo | Descripción |
|---|---|---|
| 1 | Caracterización Estudiantil | Registro, búsqueda y reportes de estudiantes con datos socioeconómicos |
| 2 | Gestión de Notas | Registro de notas por docente, materia, sección y trimestre |
| 3 | Asistencias | Control de asistencia por estudiante, sección y fecha |
| 4 | Inventario de Alimentos (Bodega) | Lotes, movimientos, auditorías y trazabilidad de alimentos |
| 5 | Recurso Humano | Gestión de personal docente y administrativo |
| 6 | Informes y Reportes | Rendimiento académico, caracterización demográfica, inventario |
| 7 | Administración | Gestión de usuarios, roles, año lectivo, grados y secciones |

---

## Convenciones Generales

- Todos los componentes de UI son **Componentes Livewire 4** (clases PHP + vistas Blade).
- Los formularios usan `wire:model` y validación con `#[Rule]` o `validate()` en el componente.
- Los reportes se generan en PDF o tabla HTML exportable.
- Las acciones sensibles (eliminar, modificar roles) requieren confirmación modal.
- El sistema aplica **RBAC** via Spatie: cada ruta y acción valida el rol/permiso activo.
- La sesión se cierra automáticamente tras **15 minutos** de inactividad.
- Solo puede existir **un año lectivo activo** a la vez (`anos_lectivos.activo = true`).
- Todos los reportes de rendimiento son requeridos por el **MINED** de forma trimestral y anual.

---

## Roles del Sistema

| Rol | Descripción |
|---|---|
| `admin` | Gestión de usuarios, roles, parametrización del año lectivo, mantenimiento |
| `director` | Lectura/escritura global, generación de reportes estratégicos e indicadores |
| `docente` | Solo gestiona notas y ve el rendimiento de sus grados/materias asignados |
| `bodega` | Acceso exclusivo al módulo de inventario de alimentos |

---

## Requisitos No Funcionales Clave

- Tiempos de respuesta < 3 segundos para reportes complejos (índices en BD).
- Backup automático semanal incremental.
- Sesión expira a los 15 minutos de inactividad.
