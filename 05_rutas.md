# Rutas del Sistema

Todas las rutas están protegidas con middleware `auth`.  
Las rutas usan **Livewire full-page components** como controladores.

---

## Definición en `routes/web.php`

```php
use Illuminate\Support\Facades\Route;
use App\Livewire\Auth\Login;
use App\Livewire\Dashboard;
use App\Livewire\Admin\GestionUsuarios;
use App\Livewire\Admin\GestionAnoLectivo;
use App\Livewire\Admin\GestionGradosSecciones;
use App\Livewire\Estudiantes\ListadoEstudiantes;
use App\Livewire\Estudiantes\FormularioEstudiante;
use App\Livewire\Estudiantes\GestionMatriculas;
use App\Livewire\Notas\SeleccionNotasDocente;
use App\Livewire\Notas\RegistroNotas;
use App\Livewire\Asistencias\RegistroAsistencia;
use App\Livewire\Asistencias\ReporteAsistencias;
use App\Livewire\Inventario\ListadoProductos;
use App\Livewire\Inventario\GestionLotes;
use App\Livewire\Inventario\RegistroMovimiento;
use App\Livewire\Inventario\AuditoriaBodega;
use App\Livewire\Personal\ListadoPersonal;
use App\Livewire\Personal\FormularioPersonal;
use App\Livewire\Personal\AsignacionesDocente;
use App\Livewire\Reportes\InformeRendimientoAcademico;
use App\Livewire\Reportes\CaracterizacionDemografica;
use App\Livewire\Reportes\ReporteInventario;
use App\Livewire\Reportes\GeneradorReporteEstadistico;

// ─── Autenticación ────────────────────────────────────────────────
Route::get('/login', Login::class)->name('login')->middleware('guest');
Route::post('/logout', [LogoutController::class, 'destroy'])->name('logout');

// ─── Dashboard ────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');
});

// ─── Administración ───────────────────────────────────────────────
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/usuarios',          GestionUsuarios::class)->name('usuarios');
    Route::get('/ano-lectivo',       GestionAnoLectivo::class)->name('ano-lectivo');
    Route::get('/grados-secciones',  GestionGradosSecciones::class)->name('grados-secciones');
});

// ─── Estudiantes ──────────────────────────────────────────────────
Route::middleware(['auth', 'role:admin|director'])->prefix('estudiantes')->name('estudiantes.')->group(function () {
    Route::get('/',           ListadoEstudiantes::class)->name('index');
    Route::get('/crear',      FormularioEstudiante::class)->name('crear');
    Route::get('/{id}/editar', FormularioEstudiante::class)->name('editar');
    Route::get('/matriculas', GestionMatriculas::class)->name('matriculas');
});

// ─── Notas ────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:admin|director|docente'])->prefix('notas')->name('notas.')->group(function () {
    Route::get('/',                              SeleccionNotasDocente::class)->name('index');
    Route::get('/registro/{asignacion}/{trimestre}', RegistroNotas::class)->name('registro');
});

// ─── Asistencias ──────────────────────────────────────────────────
Route::middleware(['auth', 'role:admin|director|docente'])->prefix('asistencias')->name('asistencias.')->group(function () {
    Route::get('/',         RegistroAsistencia::class)->name('index');
    Route::get('/reporte',  ReporteAsistencias::class)
         ->middleware('role:admin|director')
         ->name('reporte');
});

// ─── Inventario ───────────────────────────────────────────────────
Route::middleware(['auth', 'role:admin|bodega'])->prefix('inventario')->name('inventario.')->group(function () {
    Route::get('/productos',    ListadoProductos::class)->name('productos');
    Route::get('/lotes',        GestionLotes::class)->name('lotes');
    Route::get('/movimientos',  RegistroMovimiento::class)->name('movimientos');
    Route::get('/auditoria',    AuditoriaBodega::class)->name('auditoria');
});

// ─── Recurso Humano ───────────────────────────────────────────────
Route::middleware(['auth', 'role:admin|director'])->prefix('personal')->name('personal.')->group(function () {
    Route::get('/',              ListadoPersonal::class)->name('index');
    Route::get('/crear',         FormularioPersonal::class)->name('crear');
    Route::get('/{id}/editar',   FormularioPersonal::class)->name('editar');
    Route::get('/{id}/asignaciones', AsignacionesDocente::class)->name('asignaciones');
});

// ─── Reportes ─────────────────────────────────────────────────────
Route::middleware(['auth', 'role:admin|director'])->prefix('reportes')->name('reportes.')->group(function () {
    Route::get('/rendimiento',      InformeRendimientoAcademico::class)->name('rendimiento');
    Route::get('/caracterizacion',  CaracterizacionDemografica::class)->name('caracterizacion');
    Route::get('/inventario',       ReporteInventario::class)->name('inventario');
    Route::get('/estadisticos',     GeneradorReporteEstadistico::class)->name('estadisticos');
});
```

---

## Tabla de Rutas Nombradas

| Nombre | URL | Rol(es) |
|---|---|---|
| `login` | `/login` | guest |
| `dashboard` | `/` | todos |
| `admin.usuarios` | `/admin/usuarios` | admin |
| `admin.ano-lectivo` | `/admin/ano-lectivo` | admin |
| `admin.grados-secciones` | `/admin/grados-secciones` | admin |
| `estudiantes.index` | `/estudiantes` | admin, director |
| `estudiantes.crear` | `/estudiantes/crear` | admin, director |
| `estudiantes.editar` | `/estudiantes/{id}/editar` | admin, director |
| `estudiantes.matriculas` | `/estudiantes/matriculas` | admin, director |
| `notas.index` | `/notas` | admin, director, docente |
| `notas.registro` | `/notas/registro/{asignacion}/{trimestre}` | admin, director, docente |
| `asistencias.index` | `/asistencias` | admin, director, docente |
| `asistencias.reporte` | `/asistencias/reporte` | admin, director |
| `inventario.productos` | `/inventario/productos` | admin, bodega |
| `inventario.lotes` | `/inventario/lotes` | admin, bodega |
| `inventario.movimientos` | `/inventario/movimientos` | admin, bodega |
| `inventario.auditoria` | `/inventario/auditoria` | admin, bodega |
| `personal.index` | `/personal` | admin, director |
| `personal.crear` | `/personal/crear` | admin, director |
| `personal.editar` | `/personal/{id}/editar` | admin, director |
| `personal.asignaciones` | `/personal/{id}/asignaciones` | admin, director |
| `reportes.rendimiento` | `/reportes/rendimiento` | admin, director |
| `reportes.caracterizacion` | `/reportes/caracterizacion` | admin, director |
| `reportes.inventario` | `/reportes/inventario` | admin, director, bodega |
| `reportes.estadisticos` | `/reportes/estadisticos` | admin, director |

---

## Dashboard

El componente `Dashboard` muestra tarjetas de resumen según el rol:

| Rol | Tarjetas visibles |
|---|---|
| `admin` | Usuarios activos, año lectivo activo, total estudiantes, total personal |
| `director` | Total estudiantes matriculados, aprobación último trimestre, estado inventario |
| `docente` | Mis secciones, notas pendientes por registrar, asistencia de hoy |
| `bodega` | Stock crítico, movimientos del día, auditorías pendientes |
