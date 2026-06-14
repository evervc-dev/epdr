<?php

use Livewire\Component;
use App\Models\User;
use App\Models\AnoLectivo;
use App\Models\Matricula;
use App\Models\Personal;
use App\Models\AsignacionDocente;
use App\Models\RegistroNota;
use App\Models\AuditoriaBodega;
use App\Models\Asistencia;
use App\Models\LoteAlimento;
use App\Models\MovimientoInventario;

new class extends Component
{
    // General
    public string $anioActivo = 'Ninguno';

    // Admin stats
    public int $totalUsers = 0;
    public int $totalEstudiantes = 0;
    public int $totalPersonal = 0;

    // Director stats
    public int $matriculaActiva = 0;
    public string $rendimientoAcademico = '0%';
    public string $estadoBodega = 'Sin registros';

    // Docente stats
    public int $misSecciones = 0;
    public string $notasPendientes = 'Ninguna';
    public string $asistenciaHoy = 'Pendiente';

    // Bodega stats
    public string $stockCritico = 'Ninguno';
    public string $movimientosHoy = '0 Registros';
    public string $auditoriasPendientes = 'Ninguna';

    public function mount()
    {
        // 1. General Info
        $ano = AnoLectivo::where('activo', true)->first();
        $this->anioActivo = $ano ? (string)$ano->anio : 'Ninguno';

        // 2. Admin Calculations
        $this->totalUsers = User::count();
        $this->totalEstudiantes = Matricula::where('estado', 'ACTIVA')
            ->when($ano, fn($q) => $q->where('ano_lectivo_id', $ano->id))
            ->count();
        $this->totalPersonal = Personal::where('activo', true)->count();

        // 3. Director Calculations
        $this->matriculaActiva = $this->totalEstudiantes;

        $notas = RegistroNota::whereHas('asignacionDocente', function($q) use ($ano) {
            if ($ano) {
                $q->where('ano_lectivo_id', $ano->id);
            }
        })->get();
        $aprobadas = $notas->filter(fn($n) => $n->nota_final >= 5.00)->count();
        $totalNotas = $notas->count();
        $rendimientoPct = $totalNotas > 0 ? round(($aprobadas / $totalNotas) * 100) : 100;
        $this->rendimientoAcademico = $rendimientoPct . '%';

        $lastAudit = AuditoriaBodega::orderBy('fecha_auditoria', 'desc')->first();
        $this->estadoBodega = $lastAudit ? ($lastAudit->estado === 'cerrada' ? 'Óptimo' : 'En Auditoría') : 'Sin registros';

        // 4. Docente Calculations
        $personal = auth()->user()->personal;
        if ($personal) {
            $this->misSecciones = AsignacionDocente::where('personal_id', $personal->id)
                ->when($ano, fn($q) => $q->where('ano_lectivo_id', $ano->id))
                ->distinct('seccion_id')
                ->count();

            $firstAsig = AsignacionDocente::where('personal_id', $personal->id)
                ->when($ano, fn($q) => $q->where('ano_lectivo_id', $ano->id))
                ->with('materia', 'seccion')
                ->first();
            $this->notasPendientes = $firstAsig ? $firstAsig->materia->nombre . ' ' . $firstAsig->seccion->nombre_completo : 'Ninguna';

            // Check if attendance has been registered today for any of this teacher's assignments
            $asigs = AsignacionDocente::where('personal_id', $personal->id)
                ->when($ano, fn($q) => $q->where('ano_lectivo_id', $ano->id))
                ->pluck('id');
            if ($asigs->isNotEmpty()) {
                $hasAsistencia = Asistencia::whereIn('materia_id', function($q) use ($asigs) {
                        $q->select('materia_id')->from('asignaciones_docentes')->whereIn('id', $asigs);
                    })
                    ->whereDate('fecha', now()->format('Y-m-d'))
                    ->exists();
                $this->asistenciaHoy = $hasAsistencia ? 'Completada' : 'Pendiente';
            }
        }

        // 5. Bodega Calculations
        $lotes = LoteAlimento::all();
        $stockPorProducto = [];
        foreach ($lotes as $lote) {
            if ($lote->producto) {
                $stockPorProducto[$lote->producto->nombre] = ($stockPorProducto[$lote->producto->nombre] ?? 0) + $lote->stock_actual;
            }
        }
        asort($stockPorProducto);
        if (count($stockPorProducto) > 0) {
            $criticoNombre = array_key_first($stockPorProducto);
            $criticoCantidad = current($stockPorProducto);
            $this->stockCritico = $criticoNombre . ' (' . round($criticoCantidad, 1) . ' kg)';
        } else {
            $this->stockCritico = 'Sin inventario';
        }

        $movsHoy = MovimientoInventario::whereDate('fecha', now()->format('Y-m-d'))->get();
        $entradas = $movsHoy->where('tipo_movimiento', 'entrada')->count();
        $salidas = $movsHoy->whereIn('tipo_movimiento', ['salida', 'merma'])->count();
        $this->movimientosHoy = $movsHoy->count() . ' Reg (' . $entradas . ' ent, ' . $salidas . ' sal)';

        $abiertas = AuditoriaBodega::where('estado', 'abierta')->count();
        $this->auditoriasPendientes = $abiertas > 0 ? $abiertas . ' Abierta(s)' : 'Ninguna';
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.app')
            ->title('Dashboard | SIG Centro Escolar Pablo J. Aguirre');
    }
};
?>

<div class="space-y-8 animate-fade-in">
    <!-- Welcome Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-linear-to-r from-indigo-900 to-slate-900 p-8 text-white shadow-lg">
        <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-indigo-500/10 blur-2xl"></div>
        <div class="absolute -bottom-10 -left-10 h-40 w-40 rounded-full bg-slate-500/10 blur-2xl"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-2xs font-semibold bg-indigo-500/20 text-indigo-200 border border-indigo-500/30">
                    <span class="h-1 w-1 rounded-full bg-indigo-400 animate-pulse"></span>
                    Año Lectivo {{ $anioActivo }} Activo
                </span>
                <h1 class="mt-4 text-3xl font-extrabold tracking-tight">¡Bienvenido al SIG, {{ auth()->user()->name }}!</h1>
                <p class="mt-2 text-sm text-slate-300 max-w-xl">
                    Sistema de Información Gerencial para la gestión académica, administrativa y de recursos del Centro Escolar "Pablo J. Aguirre".
                </p>
            </div>
            <div class="bg-white/10 backdrop-blur-xs border border-white/10 rounded-2xl p-4 text-center min-w-[150px]">
                <span class="block text-2xs text-indigo-200 uppercase font-bold tracking-wider">Fecha Actual</span>
                <span class="block text-lg font-bold mt-1">{{ now()->translatedFormat('d \d\e F') }}</span>
                <span class="block text-xs text-slate-300">{{ now()->translatedFormat('Y') }}</span>
            </div>
        </div>
    </div>

    <!-- Dynamic Statistics Cards Grid based on Role -->
    <div>
        <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Resumen Gerencial</h2>
        
        @role('admin')
        <!-- Admin Cards -->
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <!-- User management Card -->
            <a href="{{ route('admin.usuarios') }}" class="group relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-xs transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-indigo-200">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-500">Usuarios Activos</span>
                    <div class="rounded-xl bg-rose-50 p-2.5 text-rose-600 transition-colors group-hover:bg-rose-100">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.109A2.25 2.25 0 0112.75 21.5h-1.5a2.25 2.25 0 01-2.25-2.263V19.12c0-.12-.007-.24-.022-.36M15 19.128C15 15.606 12.144 12.75 8.75 12.75c-3.394 0-6.25 2.856-6.25 6.25v.109a2.25 2.25 0 002.25 2.263h1.5a2.25 2.25 0 002.25-2.263v-.109m0-6.19c-.501.91-.786 1.957-.786 3.07M12.75 7.5a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="block text-3xl font-bold text-slate-900">{{ $totalUsers }}</span>
                    <span class="mt-1 block text-xs font-medium text-emerald-600">Gestionar accesos →</span>
                </div>
            </a>

            <!-- Año Lectivo Card -->
            <a href="{{ route('admin.ano-lectivo') }}" class="group relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-xs transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-indigo-200">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-500">Año Lectivo</span>
                    <div class="rounded-xl bg-indigo-50 p-2.5 text-indigo-600 transition-colors group-hover:bg-indigo-100">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="block text-3xl font-bold text-slate-900">{{ $anioActivo }}</span>
                    <span class="mt-1 block text-xs text-slate-500 font-medium">Año en curso</span>
                </div>
            </a>

            <!-- Estudiantes Card -->
            <a href="{{ route('estudiantes.index') }}" class="group relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-xs transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-emerald-200">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-500">Total Estudiantes</span>
                    <div class="rounded-xl bg-emerald-50 p-2.5 text-emerald-600 transition-colors group-hover:bg-emerald-100">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 019.918 5.841 50.58 50.58 0 00-2.658.814m-15.482 0a50.697 50.697 0 0115.482 0m-15.482 0L12 10.062M12 3.493V10.06" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="block text-3xl font-bold text-slate-900">{{ $totalEstudiantes }}</span>
                    <span class="mt-1 block text-xs text-slate-500 font-medium">Matrículas activas</span>
                </div>
            </a>

            <!-- Personal Card -->
            <a href="{{ route('personal.index') }}" class="group relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-xs transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-amber-200">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-500">Total Personal</span>
                    <div class="rounded-xl bg-amber-50 p-2.5 text-amber-600 transition-colors group-hover:bg-amber-100">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="block text-3xl font-bold text-slate-900">{{ $totalPersonal }}</span>
                    <span class="mt-1 block text-xs text-slate-500 font-medium">Docentes y administrativos</span>
                </div>
            </a>
        </div>
        @endrole

        @role('director')
        <!-- Director Cards -->
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <a href="{{ route('estudiantes.index') }}" class="group relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-xs transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-emerald-200">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-500">Matrícula Activa</span>
                    <div class="rounded-xl bg-emerald-50 p-2.5 text-emerald-600 transition-colors group-hover:bg-emerald-100">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="block text-3xl font-bold text-slate-900">{{ $matriculaActiva }}</span>
                    <span class="mt-1 block text-xs text-slate-500 font-medium">Estudiantes matriculados activos</span>
                </div>
            </a>

            <a href="{{ route('reportes.rendimiento') }}" class="group relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-xs transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-indigo-200">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-500">Rendimiento Académico</span>
                    <div class="rounded-xl bg-indigo-50 p-2.5 text-indigo-600 transition-colors group-hover:bg-indigo-100">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="block text-3xl font-bold text-slate-900">{{ $rendimientoAcademico }}</span>
                    <span class="mt-1 block text-xs text-emerald-600 font-medium">Aprobación en año lectivo</span>
                </div>
            </a>

            <a href="{{ route('inventario.auditoria') }}" class="group relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-xs transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-amber-200">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-500">Estado de Bodega</span>
                    <div class="rounded-xl bg-amber-50 p-2.5 text-amber-600 transition-colors group-hover:bg-amber-100">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="block text-3xl font-bold text-slate-900">{{ $estadoBodega }}</span>
                    <span class="mt-1 block text-xs text-slate-500 font-medium">Último estado de auditoría</span>
                </div>
            </a>
        </div>
        @endrole

        @role('docente')
        <!-- Docente Cards -->
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <a href="{{ route('notas.index') }}" class="group relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-xs transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-emerald-200">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-500">Mis Secciones</span>
                    <div class="rounded-xl bg-emerald-50 p-2.5 text-emerald-600 transition-colors group-hover:bg-emerald-100">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="block text-3xl font-bold text-slate-900">{{ $misSecciones }} @if($misSecciones === 1) Sección @else Secciones @endif</span>
                    <span class="mt-1 block text-xs text-slate-500 font-medium">Asignadas en año activo</span>
                </div>
            </a>

            <a href="{{ route('notas.index') }}" class="group relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-xs transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-amber-200">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-500">Carga Académica</span>
                    <div class="rounded-xl bg-amber-50 p-2.5 text-amber-600 transition-colors group-hover:bg-amber-100">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="block text-md font-bold text-slate-900 truncate leading-8">{{ $notasPendientes }}</span>
                    <span class="mt-1 block text-xs text-indigo-600 font-semibold">Gestionar notas →</span>
                </div>
            </a>

            <a href="{{ route('asistencias.index') }}" class="group relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-xs transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-indigo-200">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-500">Asistencia de Hoy</span>
                    <div class="rounded-xl bg-indigo-50 p-2.5 text-indigo-600 transition-colors group-hover:bg-indigo-100">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="block text-3xl font-bold text-slate-900">{{ $asistenciaHoy }}</span>
                    @if($asistenciaHoy === 'Completada')
                        <span class="mt-1 block text-xs text-emerald-600 font-medium">Asistencia registrada hoy</span>
                    @else
                        <span class="mt-1 block text-xs text-rose-500 font-semibold">Pendiente de registro</span>
                    @endif
                </div>
            </a>
        </div>
        @endrole

        @role('bodega')
        <!-- Bodega Cards -->
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <a href="{{ route('inventario.productos') }}" class="group relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-xs transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-rose-200">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-500">Materia con Menor Stock</span>
                    <div class="rounded-xl bg-rose-50 p-2.5 text-rose-600 transition-colors group-hover:bg-rose-100">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="block text-md font-bold text-slate-900 truncate leading-8">{{ $stockCritico }}</span>
                    <span class="mt-1 block text-xs text-rose-500 font-semibold">Revisar inventarios →</span>
                </div>
            </a>

            <a href="{{ route('inventario.movimientos') }}" class="group relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-xs transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-indigo-200">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-500">Movimientos de Hoy</span>
                    <div class="rounded-xl bg-indigo-50 p-2.5 text-indigo-600 transition-colors group-hover:bg-indigo-100">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="block text-md font-bold text-slate-900 truncate leading-8">{{ $movimientosHoy }}</span>
                    <span class="mt-1 block text-xs text-slate-500 font-medium">Bitácora de movimientos</span>
                </div>
            </a>

            <a href="{{ route('inventario.auditoria') }}" class="group relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-xs transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-emerald-200">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-500">Auditorías Pendientes</span>
                    <div class="rounded-xl bg-emerald-50 p-2.5 text-emerald-600 transition-colors group-hover:bg-emerald-100">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.125 2.25h3.75a.75.75 0 01.75.75v1.125" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="block text-3xl font-bold text-slate-900">{{ $auditoriasPendientes }}</span>
                    <span class="mt-1 block text-xs text-slate-500 font-medium">Auditorías abiertas en curso</span>
                </div>
            </a>
        </div>
        @endrole
    </div>

    <!-- Quick Action Cards or Information panels -->
    <div class="grid gap-6 md:grid-cols-2">
        <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-2xs">
            <h3 class="text-lg font-bold text-slate-900 mb-2">Accesos Directos rápidos</h3>
            <p class="text-sm text-slate-500 mb-6">Accede a las opciones más frecuentes de tu perfil.</p>
            
            <div class="grid grid-cols-2 gap-4">
                @role('admin')
                <a href="{{ route('admin.usuarios') }}" class="flex flex-col items-center justify-center p-4 rounded-2xl border border-slate-100 bg-slate-50 hover:bg-indigo-50/20 hover:border-indigo-200 text-center transition-all duration-150">
                    <div class="h-10 w-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-2">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372" />
                        </svg>
                    </div>
                    <span class="text-xs font-bold text-slate-950">Gestionar Usuarios</span>
                </a>
                @endrole

                <a href="{{ route('horarios.index') }}" class="flex flex-col items-center justify-center p-4 rounded-2xl border border-slate-100 bg-slate-50 hover:bg-indigo-50/20 hover:border-indigo-200 text-center transition-all duration-150">
                    <div class="h-10 w-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-2">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <span class="text-xs font-bold text-slate-950">Ver Horarios</span>
                </a>

                <a href="mailto:soporte@cepja.edu.sv" class="flex flex-col items-center justify-center p-4 rounded-2xl border border-slate-100 bg-slate-50 hover:bg-indigo-50/20 hover:border-indigo-200 text-center transition-all duration-150">
                    <div class="h-10 w-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-2">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <span class="text-xs font-bold text-slate-950">Ayuda y Soporte</span>
                </a>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-2xs flex flex-col justify-between">
            <div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Información de la Institución</h3>
                <p class="text-sm text-slate-500">Detalles de contacto y ubicación del centro escolar.</p>
                
                <div class="mt-6 space-y-4">
                    <div class="flex items-center gap-3 text-sm text-slate-600">
                        <svg class="h-5 w-5 text-indigo-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25s-7.5-4.108-7.5-11.25A7.5 7.5 0 0119.5 10.5z" />
                        </svg>
                        <span>6a Avenida Norte #401, San Miguel, El Salvador</span>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-slate-600">
                        <svg class="h-5 w-5 text-indigo-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-1.514 2.018a14.992 14.992 0 01-8.184-8.184l2.018-1.514c.362-.272.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                        </svg>
                        <span>+503 0000-0000</span>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <span>Versión del Sistema: 1.0.0</span>
                <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-emerald-500"></span> Online</span>
            </div>
        </div>
    </div>
</div>
