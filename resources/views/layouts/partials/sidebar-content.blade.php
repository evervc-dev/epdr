<div class="flex flex-col h-full bg-slate-900 text-slate-300">
    <!-- Brand / Header -->
    <div class="flex h-16 shrink-0 items-center px-6 border-b border-slate-800 bg-slate-950/40">
        <div class="h-9 w-9 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-black text-lg shadow-md shadow-indigo-500/20 mr-3">
            SIG
        </div>
        <div>
            <span class="block text-sm font-bold text-white leading-tight">SIG Gerencial</span>
            <span class="block text-2xs text-slate-500">C.E. Pablo J. Aguirre</span>
        </div>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-6">
        <!-- Main / Dashboard -->
        <div>
            <span class="px-2 text-3xs font-extrabold tracking-wider text-slate-500 uppercase">General</span>
            <div class="mt-2 space-y-1">
                <a 
                    href="{{ route('dashboard') }}" 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/10' : 'hover:bg-slate-800 hover:text-white' }}"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                    Dashboard
                </a>
            </div>
        </div>

        <!-- Módulo: Administración (Only Admin) -->
        @role('admin')
        <div>
            <span class="px-2 text-3xs font-extrabold tracking-wider text-slate-500 uppercase">Administración</span>
            <div class="mt-2 space-y-1">
                <a 
                    href="{{ route('admin.usuarios') }}" 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 {{ request()->routeIs('admin.usuarios') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/10' : 'hover:bg-slate-800 hover:text-white' }}"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.109A2.25 2.25 0 0112.75 21.5h-1.5a2.25 2.25 0 01-2.25-2.263V19.12c0-.12-.007-.24-.022-.36M15 19.128C15 15.606 12.144 12.75 8.75 12.75c-3.394 0-6.25 2.856-6.25 6.25v.109a2.25 2.25 0 002.25 2.263h1.5a2.25 2.25 0 002.25-2.263v-.109m0-6.19c-.501.91-.786 1.957-.786 3.07M12.75 7.5a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zM18.75 10.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                    </svg>
                    Gestión de Usuarios
                </a>
                <a 
                    href="{{ route('admin.ano-lectivo') }}" 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 {{ request()->routeIs('admin.ano-lectivo') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/10' : 'hover:bg-slate-800 hover:text-white' }}"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                    Año Lectivo
                </a>
                <a 
                    href="{{ route('admin.grados-secciones') }}" 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 {{ request()->routeIs('admin.grados-secciones') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/10' : 'hover:bg-slate-800 hover:text-white' }}"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.33l-7.5-5-7.5 5V21" />
                    </svg>
                    Grados y Secciones
                </a>
                <a 
                    href="{{ route('admin.materias') }}" 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 {{ request()->routeIs('admin.materias') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/10' : 'hover:bg-slate-800 hover:text-white' }}"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                    Materias
                </a>
            </div>
        </div>
        @endrole

        <!-- Módulo Académico (admin, director, docente) -->
        @role('admin|director|docente')
        <div>
            <span class="px-2 text-3xs font-extrabold tracking-wider text-slate-500 uppercase">Gestión Académica</span>
            <div class="mt-2 space-y-1">
                @role('admin|director')
                <a 
                    href="{{ route('estudiantes.index') }}" 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 {{ request()->routeIs('estudiantes.index') || request()->routeIs('estudiantes.crear') || request()->routeIs('estudiantes.editar') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/10' : 'hover:bg-slate-800 hover:text-white' }}"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 019.918 5.841 50.58 50.58 0 00-2.658.814m-15.482 0a50.697 50.697 0 0115.482 0m-15.482 0L12 10.062M12 3.493V10.06m0 0L20.25 8" />
                    </svg>
                    Estudiantes
                </a>
                <a 
                    href="{{ route('estudiantes.matriculas') }}" 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 {{ request()->routeIs('estudiantes.matriculas') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/10' : 'hover:bg-slate-800 hover:text-white' }}"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Matrículas
                </a>
                @endrole
                
                <a 
                    href="{{ route('asistencias.index') }}" 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 {{ request()->routeIs('asistencias.index') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/10' : 'hover:bg-slate-800 hover:text-white' }}"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    Registrar Asistencia
                </a>
                <a 
                    href="{{ route('horarios.index') }}" 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 {{ request()->routeIs('horarios.index') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/10' : 'hover:bg-slate-800 hover:text-white' }}"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Horarios de Clase
                </a>
                @role('admin|director')
                <a 
                    href="{{ route('asistencias.reporte') }}" 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 {{ request()->routeIs('asistencias.reporte') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/10' : 'hover:bg-slate-800 hover:text-white' }}"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z" />
                    </svg>
                    Reporte Asistencias
                </a>
                @endrole
                <a 
                    href="{{ route('notas.index') }}" 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 {{ request()->routeIs('notas.index') || request()->routeIs('notas.registro') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/10' : 'hover:bg-slate-800 hover:text-white' }}"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                    </svg>
                    Gestión de Notas
                </a>
            </div>
        </div>
        @endrole

        <!-- Módulo Inventario (admin, director, bodega) -->
        @role('admin|director|bodega')
        <div>
            <span class="px-2 text-3xs font-extrabold tracking-wider text-slate-500 uppercase">Inventario Bodega</span>
            <div class="mt-2 space-y-1">
                @role('admin|bodega')
                <a 
                    href="{{ route('inventario.productos') }}" 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 {{ request()->routeIs('inventario.productos') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/10' : 'hover:bg-slate-800 hover:text-white' }}"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                    Productos
                </a>
                <a 
                    href="{{ route('inventario.lotes') }}" 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 {{ request()->routeIs('inventario.lotes') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/10' : 'hover:bg-slate-800 hover:text-white' }}"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.875 14.25l1.214 1.942a2.25 2.25 0 001.896 1.058h2.03c.752 0 1.452-.372 1.896-1.058l1.214-1.942M2.41 9h19.18M2.41 9a2.25 2.25 0 00-1.896 3.125l3.824 6.12A2.25 2.25 0 006.232 19.5h11.536a2.25 2.25 0 001.896-1.255l3.824-6.12A2.25 2.25 0 0021.59 9M2.41 9a2.25 2.25 0 011.896-1.255h15.284A2.25 2.25 0 0121.59 9" />
                    </svg>
                    Lotes de Alimentos
                </a>
                <a 
                    href="{{ route('inventario.movimientos') }}" 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 {{ request()->routeIs('inventario.movimientos') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/10' : 'hover:bg-slate-800 hover:text-white' }}"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                    </svg>
                    Movimientos
                </a>
                @endrole
                <a 
                    href="{{ route('inventario.auditoria') }}" 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 {{ request()->routeIs('inventario.auditoria') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/10' : 'hover:bg-slate-800 hover:text-white' }}"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.125 2.25h3.75a.75.75 0 01.75.75v1.125c0 .414.336.75.75.75h4.5a.75.75 0 01.75.75v15a.75.75 0 01-.75.75h-10.5a.75.75 0 01-.75-.75v-15a.75.75 0 01.75-.75h4.5c.414 0 .75-.336.75-.75V3a.75.75 0 01.75-.75z" />
                    </svg>
                    Auditorías
                </a>
            </div>
        </div>
        @endrole

        <!-- Módulo Recurso Humano (admin, director) -->
        @role('admin|director')
        <div>
            <span class="px-2 text-3xs font-extrabold tracking-wider text-slate-500 uppercase">Recurso Humano</span>
            <div class="mt-2 space-y-1">
                <a 
                    href="{{ route('personal.index') }}" 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 {{ request()->routeIs('personal.index') || request()->routeIs('personal.crear') || request()->routeIs('personal.editar') || request()->routeIs('personal.asignaciones') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/10' : 'hover:bg-slate-800 hover:text-white' }}"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                    </svg>
                    Gestión de Personal
                </a>
            </div>
        </div>
        @endrole

        <!-- Módulo Reportes (admin, director, bodega) -->
        @role('admin|director|bodega')
        <div>
            <span class="px-2 text-3xs font-extrabold tracking-wider text-slate-500 uppercase">Reportes e Informes</span>
            <div class="mt-2 space-y-1">
                @role('admin|director')
                <a 
                    href="{{ route('reportes.rendimiento') }}" 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 {{ request()->routeIs('reportes.rendimiento') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/10' : 'hover:bg-slate-800 hover:text-white' }}"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    Informes de Rendimiento
                </a>
                <a 
                    href="{{ route('reportes.caracterizacion') }}" 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 {{ request()->routeIs('reportes.caracterizacion') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/10' : 'hover:bg-slate-800 hover:text-white' }}"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                    Caracterización Demográfica
                </a>
                <a 
                    href="{{ route('reportes.estadisticos') }}" 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 {{ request()->routeIs('reportes.estadisticos') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/10' : 'hover:bg-slate-800 hover:text-white' }}"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v5.25c0 .621-.504 1.125-1.125 1.125h-2.25A1.125 1.125 0 013 18.375v-5.25zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125v-9.75zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v14.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                    </svg>
                    Reportes Estadísticos
                </a>
                @endrole
                
                <a 
                    href="{{ route('reportes.inventario') }}" 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 {{ request()->routeIs('reportes.inventario') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/10' : 'hover:bg-slate-800 hover:text-white' }}"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5" />
                    </svg>
                    Reporte de Inventario
                </a>
            </div>
        </div>
        @endrole
    </nav>

    <!-- Footer info -->
    <div class="p-4 border-t border-slate-800 bg-slate-950/20 text-center text-3xs text-slate-500">
        © 2026 C.E. Pablo J. Aguirre
    </div>
</div>
