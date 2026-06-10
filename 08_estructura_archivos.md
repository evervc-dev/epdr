# Estructura de Archivos del Proyecto

Estructura esperada del proyecto Laravel una vez implementadas todas las especificaciones.

---

```
app/
├── Livewire/
│   ├── Auth/
│   │   └── Login.php                          ✅ (ya existe)
│   ├── Dashboard.php
│   ├── Admin/
│   │   ├── GestionUsuarios.php
│   │   ├── GestionAnoLectivo.php
│   │   └── GestionGradosSecciones.php
│   ├── Estudiantes/
│   │   ├── ListadoEstudiantes.php
│   │   ├── FormularioEstudiante.php
│   │   ├── FormularioTutor.php
│   │   └── GestionMatriculas.php
│   ├── Notas/
│   │   ├── SeleccionNotasDocente.php
│   │   └── RegistroNotas.php
│   ├── Asistencias/
│   │   ├── RegistroAsistencia.php
│   │   └── ReporteAsistencias.php
│   ├── Inventario/
│   │   ├── ListadoProductos.php
│   │   ├── GestionLotes.php
│   │   ├── RegistroMovimiento.php
│   │   └── AuditoriaBodega.php
│   ├── Personal/
│   │   ├── ListadoPersonal.php
│   │   ├── FormularioPersonal.php
│   │   └── AsignacionesDocente.php
│   └── Reportes/
│       ├── InformeRendimientoAcademico.php
│       ├── CaracterizacionDemografica.php
│       ├── ReporteInventario.php
│       └── GeneradorReporteEstadistico.php
│
├── Models/
│   ├── User.php                               ✅ (extender con HasRoles + relación personal)
│   ├── AnoLectivo.php
│   ├── Grado.php
│   ├── Seccion.php
│   ├── Estudiante.php
│   ├── TutorFamiliar.php
│   ├── Matricula.php
│   ├── Personal.php
│   ├── Materia.php
│   ├── AsignacionDocente.php
│   ├── RegistroNota.php
│   ├── Asistencia.php
│   ├── Producto.php
│   ├── LoteAlimento.php
│   ├── MovimientoInventario.php
│   ├── AuditoriaBodega.php
│   ├── DetalleAuditoriaBodega.php
│   ├── InformeRendimiento.php
│   └── ReporteEstadistico.php
│
└── Policies/                                  (opcional, para autorización granular)
    ├── NotaPolicy.php
    └── AsistenciaPolicy.php

database/
├── migrations/
│   ├── xxxx_create_anos_lectivos_table.php
│   ├── xxxx_create_grados_table.php
│   ├── xxxx_create_secciones_table.php
│   ├── xxxx_create_estudiantes_table.php
│   ├── xxxx_create_tutores_familiares_table.php
│   ├── xxxx_create_estudiante_tutor_table.php
│   ├── xxxx_create_matriculas_table.php
│   ├── xxxx_create_personal_table.php
│   ├── xxxx_create_materias_table.php
│   ├── xxxx_create_asignaciones_docentes_table.php
│   ├── xxxx_create_registro_notas_table.php
│   ├── xxxx_create_asistencias_table.php
│   ├── xxxx_create_productos_table.php
│   ├── xxxx_create_lotes_alimentos_table.php
│   ├── xxxx_create_movimientos_inventario_table.php
│   ├── xxxx_create_auditorias_bodega_table.php
│   ├── xxxx_create_detalle_auditorias_bodega_table.php
│   ├── xxxx_create_informes_rendimiento_table.php
│   └── xxxx_create_reportes_estadisticos_table.php
│
└── seeders/
    ├── DatabaseSeeder.php
    ├── RolesPermisosSeeder.php
    ├── AnoLectivoSeeder.php
    ├── GradosSeccionesSeeder.php
    ├── MateriasSeeder.php
    └── AdminUserSeeder.php

resources/
└── views/
    ├── layouts/
    │   ├── app.blade.php                      (layout principal autenticado)
    │   └── guest.blade.php                    ✅ (ya existe para login)
    ├── livewire/
    │   ├── auth/
    │   │   └── login.blade.php                ✅ (ya existe)
    │   ├── dashboard.blade.php
    │   ├── admin/
    │   │   ├── gestion-usuarios.blade.php
    │   │   ├── gestion-ano-lectivo.blade.php
    │   │   └── gestion-grados-secciones.blade.php
    │   ├── estudiantes/
    │   │   ├── listado-estudiantes.blade.php
    │   │   ├── formulario-estudiante.blade.php
    │   │   ├── formulario-tutor.blade.php
    │   │   └── gestion-matriculas.blade.php
    │   ├── notas/
    │   │   ├── seleccion-notas-docente.blade.php
    │   │   └── registro-notas.blade.php
    │   ├── asistencias/
    │   │   ├── registro-asistencia.blade.php
    │   │   └── reporte-asistencias.blade.php
    │   ├── inventario/
    │   │   ├── listado-productos.blade.php
    │   │   ├── gestion-lotes.blade.php
    │   │   ├── registro-movimiento.blade.php
    │   │   └── auditoria-bodega.blade.php
    │   ├── personal/
    │   │   ├── listado-personal.blade.php
    │   │   ├── formulario-personal.blade.php
    │   │   └── asignaciones-docente.blade.php
    │   └── reportes/
    │       ├── informe-rendimiento-academico.blade.php
    │       ├── caracterizacion-demografica.blade.php
    │       ├── reporte-inventario.blade.php
    │       └── generador-reporte-estadistico.blade.php
    ├── reportes/
    │   ├── layout-pdf.blade.php               (layout base para PDFs)
    │   ├── pdf-rendimiento.blade.php
    │   ├── pdf-caracterizacion.blade.php
    │   ├── pdf-notas.blade.php
    │   └── pdf-inventario.blade.php
    └── components/
        ├── modal-confirm.blade.php
        ├── toast-notifications.blade.php
        ├── tabla-paginada.blade.php
        └── badge-rol.blade.php

routes/
└── web.php

config/
└── session.php                                (lifetime = 15)
```

---

## Componentes Blade Reutilizables

### `<x-modal-confirm>`

Modal de confirmación para acciones destructivas.  
Props: `title`, `message`, `confirmAction` (nombre del método Livewire a llamar), `wire:model` para el estado abierto/cerrado.

### `<x-toast-notifications>`

Componente global que escucha el evento `notify` de Livewire.  
Props del evento: `type` (success/error/warning/info), `message`.  
Se incluye una sola vez en `layouts/app.blade.php`.

### `<x-tabla-paginada>`

Wrapper que renderiza los links de paginación de Laravel con estilos de Tailwind.

### `<x-badge-rol>`

Muestra el rol del usuario con color distintivo:
- `admin` → rojo
- `director` → azul
- `docente` → verde
- `bodega` → amarillo
