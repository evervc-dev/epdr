# Roles, Permisos y Control de Acceso (RBAC)

Implementado con **Spatie Laravel Permission**. Toda la lógica de autorización usa `can()`, `hasRole()` y middleware `role:` / `permission:`.

---

## Roles

| Rol | Slug |
|---|---|
| Administrador del Sistema | `admin` |
| Director / Personal Administrativo | `director` |
| Docente | `docente` |
| Encargado de Bodega/Alimentos | `bodega` |

---

## Permisos por Módulo

### Módulo: Administración

| Permiso | Admin | Director | Docente | Bodega |
|---|:---:|:---:|:---:|:---:|
| `usuarios.ver` | ✅ | ❌ | ❌ | ❌ |
| `usuarios.crear` | ✅ | ❌ | ❌ | ❌ |
| `usuarios.editar` | ✅ | ❌ | ❌ | ❌ |
| `usuarios.eliminar` | ✅ | ❌ | ❌ | ❌ |
| `roles.gestionar` | ✅ | ❌ | ❌ | ❌ |
| `anio_lectivo.gestionar` | ✅ | ❌ | ❌ | ❌ |
| `grados.gestionar` | ✅ | ❌ | ❌ | ❌ |
| `secciones.gestionar` | ✅ | ❌ | ❌ | ❌ |
| `materias.gestionar` | ✅ | ❌ | ❌ | ❌ |

### Módulo: Caracterización Estudiantil

| Permiso | Admin | Director | Docente | Bodega |
|---|:---:|:---:|:---:|:---:|
| `estudiantes.ver` | ✅ | ✅ | ✅ | ❌ |
| `estudiantes.crear` | ✅ | ✅ | ❌ | ❌ |
| `estudiantes.editar` | ✅ | ✅ | ❌ | ❌ |
| `estudiantes.eliminar` | ✅ | ❌ | ❌ | ❌ |
| `matriculas.gestionar` | ✅ | ✅ | ❌ | ❌ |
| `tutores.gestionar` | ✅ | ✅ | ❌ | ❌ |
| `reportes.caracterizacion` | ✅ | ✅ | ❌ | ❌ |

### Módulo: Notas

| Permiso | Admin | Director | Docente | Bodega |
|---|:---:|:---:|:---:|:---:|
| `notas.ver_todas` | ✅ | ✅ | ❌ | ❌ |
| `notas.ver_propias` | ✅ | ✅ | ✅ | ❌ |
| `notas.registrar` | ✅ | ✅ | ✅* | ❌ |
| `notas.editar` | ✅ | ✅ | ✅* | ❌ |
| `asignaciones.gestionar` | ✅ | ✅ | ❌ | ❌ |

> *El docente solo puede registrar/editar notas de sus propias asignaciones.

### Módulo: Asistencias

| Permiso | Admin | Director | Docente | Bodega |
|---|:---:|:---:|:---:|:---:|
| `asistencias.ver_todas` | ✅ | ✅ | ❌ | ❌ |
| `asistencias.ver_propias` | ✅ | ✅ | ✅ | ❌ |
| `asistencias.registrar` | ✅ | ✅ | ✅* | ❌ |
| `reportes.asistencias` | ✅ | ✅ | ❌ | ❌ |

> *El docente solo puede registrar asistencias de secciones donde tiene asignación.

### Módulo: Inventario de Alimentos

| Permiso | Admin | Director | Docente | Bodega |
|---|:---:|:---:|:---:|:---:|
| `inventario.ver` | ✅ | ✅ | ❌ | ✅ |
| `inventario.productos` | ✅ | ❌ | ❌ | ✅ |
| `inventario.lotes` | ✅ | ❌ | ❌ | ✅ |
| `inventario.movimientos` | ✅ | ❌ | ❌ | ✅ |
| `inventario.auditorias` | ✅ | ✅ | ❌ | ✅ |
| `reportes.inventario` | ✅ | ✅ | ❌ | ✅ |

### Módulo: Recurso Humano

| Permiso | Admin | Director | Docente | Bodega |
|---|:---:|:---:|:---:|:---:|
| `personal.ver` | ✅ | ✅ | ❌ | ❌ |
| `personal.crear` | ✅ | ✅ | ❌ | ❌ |
| `personal.editar` | ✅ | ✅ | ❌ | ❌ |
| `personal.eliminar` | ✅ | ❌ | ❌ | ❌ |

### Módulo: Reportes e Informes

| Permiso | Admin | Director | Docente | Bodega |
|---|:---:|:---:|:---:|:---:|
| `informes.rendimiento` | ✅ | ✅ | ❌ | ❌ |
| `reportes.estadisticos` | ✅ | ✅ | ❌ | ❌ |

---

## Seeder de Roles y Permisos

Crear `database/seeders/RolesPermisosSeeder.php` con:

```php
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

// 1. Crear todos los permisos listados arriba
$permisos = [
    'usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'usuarios.eliminar',
    'roles.gestionar', 'anio_lectivo.gestionar', 'grados.gestionar',
    'secciones.gestionar', 'materias.gestionar',
    'estudiantes.ver', 'estudiantes.crear', 'estudiantes.editar', 'estudiantes.eliminar',
    'matriculas.gestionar', 'tutores.gestionar', 'reportes.caracterizacion',
    'notas.ver_todas', 'notas.ver_propias', 'notas.registrar', 'notas.editar',
    'asignaciones.gestionar',
    'asistencias.ver_todas', 'asistencias.ver_propias', 'asistencias.registrar',
    'reportes.asistencias',
    'inventario.ver', 'inventario.productos', 'inventario.lotes',
    'inventario.movimientos', 'inventario.auditorias', 'reportes.inventario',
    'personal.ver', 'personal.crear', 'personal.editar', 'personal.eliminar',
    'informes.rendimiento', 'reportes.estadisticos',
];

foreach ($permisos as $permiso) {
    Permission::firstOrCreate(['name' => $permiso]);
}

// 2. Crear roles y asignar permisos según la tabla de arriba
$admin = Role::firstOrCreate(['name' => 'admin']);
$admin->syncPermissions(Permission::all());

// director, docente, bodega — asignar según tabla
```

---

## Middleware y Protección de Rutas

```php
// routes/web.php
Route::middleware(['auth', 'role:admin'])->group(function () {
    // Rutas de administración
});

Route::middleware(['auth', 'role:admin|director'])->group(function () {
    // Rutas de reportes y caracterización
});

Route::middleware(['auth', 'role:docente'])->group(function () {
    // Rutas de notas y asistencias propias
});

Route::middleware(['auth', 'role:bodega'])->group(function () {
    // Rutas de inventario
});
```

---

## Autorización en Componentes Livewire

En cada componente, validar al montar y en cada acción:

```php
public function mount()
{
    $this->authorize('ver_notas'); // O usar: abort_unless(auth()->user()->can('...'), 403);
}
```

Para el docente, verificar además que la asignación pertenece al usuario autenticado antes de cualquier escritura.

---

## Cierre Automático de Sesión

Configurar en `config/session.php`:

```php
'lifetime' => 15, // minutos
'expire_on_close' => false,
```

Y en el middleware de autenticación de Livewire agregar re-verificación en cada request.
