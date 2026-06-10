<?php

use Livewire\Component;
use App\Models\User;

new class extends Component
{
    public int $totalUsers = 0;

    public function mount()
    {
        $this->totalUsers = User::count();
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
                    Año Lectivo 2026 Activo
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
            <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-500">Año Lectivo</span>
                    <div class="rounded-xl bg-indigo-50 p-2.5 text-indigo-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="block text-3xl font-bold text-slate-900">2026</span>
                    <span class="mt-1 block text-xs text-slate-500 font-medium">Activo e inicializado</span>
                </div>
            </div>

            <!-- Estudiantes Card -->
            <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-500">Total Estudiantes</span>
                    <div class="rounded-xl bg-emerald-50 p-2.5 text-emerald-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 019.918 5.841 50.58 50.58 0 00-2.658.814m-15.482 0a50.697 50.697 0 0115.482 0m-15.482 0L12 10.062M12 3.493V10.06" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="block text-3xl font-bold text-slate-900">340</span>
                    <span class="mt-1 block text-xs text-slate-500 font-medium">Estudiantes matriculados</span>
                </div>
            </div>

            <!-- Personal Card -->
            <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-500">Total Personal</span>
                    <div class="rounded-xl bg-amber-50 p-2.5 text-amber-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="block text-3xl font-bold text-slate-900">18</span>
                    <span class="mt-1 block text-xs text-slate-500 font-medium">Docentes y administrativos</span>
                </div>
            </div>
        </div>
        @endrole

        @role('director')
        <!-- Director Cards -->
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-500">Matrícula Activa</span>
                    <div class="rounded-xl bg-emerald-50 p-2.5 text-emerald-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="block text-3xl font-bold text-slate-900">324</span>
                    <span class="mt-1 block text-xs text-slate-500 font-medium">Estudiantes matriculados activos</span>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-500">Rendimiento Académico</span>
                    <div class="rounded-xl bg-indigo-50 p-2.5 text-indigo-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="block text-3xl font-bold text-slate-900">92%</span>
                    <span class="mt-1 block text-xs text-emerald-600 font-medium">Aprobación último trimestre</span>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-500">Estado de Bodega</span>
                    <div class="rounded-xl bg-amber-50 p-2.5 text-amber-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="block text-3xl font-bold text-slate-900">Óptimo</span>
                    <span class="mt-1 block text-xs text-slate-500 font-medium">Última auditoría cerrada</span>
                </div>
            </div>
        </div>
        @endrole

        @role('docente')
        <!-- Docente Cards -->
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-500">Mis Secciones</span>
                    <div class="rounded-xl bg-emerald-50 p-2.5 text-emerald-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="block text-3xl font-bold text-slate-900">3 Secciones</span>
                    <span class="mt-1 block text-xs text-slate-500 font-medium">Asignadas en año activo</span>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-500">Notas Pendientes</span>
                    <div class="rounded-xl bg-amber-50 p-2.5 text-amber-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="block text-3xl font-bold text-slate-900">Lenguaje 5°B</span>
                    <span class="mt-1 block text-xs text-rose-500 font-semibold">Trimestre 1</span>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-500">Asistencia de Hoy</span>
                    <div class="rounded-xl bg-indigo-50 p-2.5 text-indigo-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="block text-3xl font-bold text-slate-900">Completada</span>
                    <span class="mt-1 block text-xs text-emerald-600 font-medium">Asistencia diaria registrada</span>
                </div>
            </div>
        </div>
        @endrole

        @role('bodega')
        <!-- Bodega Cards -->
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-500">Stock Crítico</span>
                    <div class="rounded-xl bg-rose-50 p-2.5 text-rose-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="block text-3xl font-bold text-slate-900">Leche en Polvo</span>
                    <span class="mt-1 block text-xs text-rose-500 font-semibold">Quedan 3 sacos</span>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-500">Movimientos de Hoy</span>
                    <div class="rounded-xl bg-indigo-50 p-2.5 text-indigo-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="block text-3xl font-bold text-slate-900">6 Registros</span>
                    <span class="mt-1 block text-xs text-slate-500 font-medium">4 entradas, 2 salidas hoy</span>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-500">Auditorías Pendientes</span>
                    <div class="rounded-xl bg-emerald-50 p-2.5 text-emerald-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.125 2.25h3.75a.75.75 0 01.75.75v1.125" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="block text-3xl font-bold text-slate-900">Ninguna</span>
                    <span class="mt-1 block text-xs text-slate-500 font-medium">Todo al día</span>
                </div>
            </div>
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
                <a href="{{ route('admin.usuarios') }}" class="flex flex-col items-center justify-center p-4 rounded-2xl border border-slate-100 bg-slate-50 hover:bg-slate-100 hover:border-slate-200 text-center transition-all duration-150">
                    <div class="h-10 w-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-2">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372" />
                        </svg>
                    </div>
                    <span class="text-xs font-bold text-slate-950">Gestionar Usuarios</span>
                </a>
                @endrole

                <a href="#" class="flex flex-col items-center justify-center p-4 rounded-2xl border border-slate-100 bg-slate-50/50 opacity-55 text-center cursor-not-allowed">
                    <div class="h-10 w-10 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center mb-2">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <span class="text-xs font-bold text-slate-400">Ver Horarios</span>
                </a>

                <a href="#" class="flex flex-col items-center justify-center p-4 rounded-2xl border border-slate-100 bg-slate-50/50 opacity-55 text-center cursor-not-allowed">
                    <div class="h-10 w-10 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center mb-2">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 0v3m0-3h3m-3 0H9" />
                        </svg>
                    </div>
                    <span class="text-xs font-bold text-slate-400">Ayuda y Soporte</span>
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
                        <span>Cantón El Roble, Ahuachapán, El Salvador</span>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-slate-600">
                        <svg class="h-5 w-5 text-indigo-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-1.514 2.018a14.992 14.992 0 01-8.184-8.184l2.018-1.514c.362-.272.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                        </svg>
                        <span>+503 2400-0000</span>
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
