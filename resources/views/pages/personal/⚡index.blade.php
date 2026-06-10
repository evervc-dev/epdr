<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Personal;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filtroTipo = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filtroTipo' => ['except' => ''],
    ];

    public function mount()
    {
        abort_unless(auth()->user()->can('personal.ver'), 403);
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFiltroTipo() { $this->resetPage(); }

    public function getPersonal()
    {
        return Personal::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('dui', 'like', '%' . $this->search . '%')
                      ->orWhere('nombres', 'like', '%' . $this->search . '%')
                      ->orWhere('apellidos', 'like', '%' . $this->search . '%')
                      ->orWhere('correo', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filtroTipo, fn($q) => $q->where('tipo', $this->filtroTipo))
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->paginate(15);
    }

    public function toggleActivo($id)
    {
        abort_unless(auth()->user()->can('personal.editar'), 403);
        $persona = Personal::findOrFail($id);
        $persona->update(['activo' => !$persona->activo]);

        $this->dispatch('notify', message: 'Estado del personal actualizado con éxito.', type: 'success');
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.app')
            ->title('Listado de Personal | SIG');
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Gestión de Personal</h1>
            <p class="mt-1 text-sm text-slate-500">Expediente de recursos humanos del centro escolar (docentes, administrativos y servicios).</p>
        </div>
        <div>
            @can('personal.crear')
            <a 
                href="{{ route('personal.crear') }}"
                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:shadow transition"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nuevo Personal
            </a>
            @endcan
        </div>
    </div>

    <!-- Filters & Table Card -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs overflow-hidden">
        
        <!-- Filters panel -->
        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Search bar -->
                <div class="relative md:col-span-2">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.637 10.637z" />
                        </svg>
                    </div>
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="search"
                        placeholder="Buscar por DUI, nombre o correo..." 
                        class="block w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-950 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                    />
                </div>

                <!-- Tipo filter -->
                <div>
                    <select 
                        wire:model.live="filtroTipo"
                        class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-950 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                    >
                        <option value="">Todos los Roles...</option>
                        <option value="docente">Docente</option>
                        <option value="administrativo">Administrativo</option>
                        <option value="servicio">Servicios Generales</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">DUI</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Nombre Completo</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Rol / Tipo</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Teléfono / Correo</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($this->getPersonal() as $persona)
                        <tr class="hover:bg-slate-50/30 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-700">
                                {{ $persona->dui }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-slate-900">{{ $persona->nombre_completo }}</div>
                                <div class="text-xs text-slate-500">Ingreso: {{ $persona->fecha_ingreso->format('d/m/Y') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($persona->tipo === 'docente')
                                    <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/10">
                                        Docente
                                    </span>
                                    @if($persona->especialidad)
                                        <div class="text-3xs text-slate-400 mt-0.5">{{ $persona->especialidad }}</div>
                                    @endif
                                @elseif($persona->tipo === 'administrativo')
                                    <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-600/10">
                                        Administrativo
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/10">
                                        Servicios
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600">
                                <div>{{ $persona->telefono ?: '-' }}</div>
                                <div class="text-slate-400 text-3xs">{{ $persona->correo ?: 'Sin correo' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($persona->activo)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/10">
                                        Activo
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/10">
                                        Inactivo
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if($persona->tipo === 'docente' && $persona->activo)
                                        @can('asignaciones.gestionar')
                                        <a 
                                            href="{{ route('personal.asignaciones', $persona->id) }}"
                                            class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:text-indigo-500 hover:bg-indigo-50 px-2.5 py-1.5 rounded-lg transition"
                                            title="Asignar Materias"
                                        >
                                            Asignaciones
                                        </a>
                                        @endcan
                                    @endif

                                    @can('personal.editar')
                                    <button 
                                        type="button"
                                        wire:click="toggleActivo({{ $persona->id }})"
                                        class="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-50 rounded-lg transition"
                                        title="{{ $persona->activo ? 'Desactivar' : 'Activar' }}"
                                    >
                                        @if($persona->activo)
                                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                            </svg>
                                        @else
                                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        @endif
                                    </button>

                                    <a 
                                        href="{{ route('personal.editar', $persona->id) }}"
                                        class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-slate-50 rounded-lg transition"
                                        title="Editar"
                                    >
                                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                        </svg>
                                    </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center">
                                <div class="max-w-xs mx-auto text-slate-400">
                                    <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.109A2.25 2.25 0 0112.75 21.5h-1.5a2.25 2.25 0 01-2.25-2.263V19.12c0-.12-.007-.24-.022-.36M15 19.128C15 15.606 12.144 12.75 8.75 12.75c-3.394 0-6.25 2.856-6.25 6.25v.109a2.25 2.25 0 002.25 2.263h1.5a2.25 2.25 0 002.25-2.263v-.109m0-6.19c-.501.91-.786 1.957-.786 3.07M12.75 7.5a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zM18.75 10.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                                    </svg>
                                    <p class="mt-2 text-sm font-semibold text-slate-700">No se encontraron registros de personal</p>
                                    <p class="mt-1 text-xs">Intenta cambiar los filtros o el término de búsqueda.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <x-tabla-paginada :items="$this->getPersonal()" />
    </div>
</div>
