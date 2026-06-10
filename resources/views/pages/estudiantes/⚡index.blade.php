<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Seccion;
use App\Models\AnoLectivo;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filtroGrado = '';
    public string $filtroSeccion = '';
    public string $filtroAnio = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filtroGrado' => ['except' => ''],
        'filtroSeccion' => ['except' => ''],
        'filtroAnio' => ['except' => ''],
    ];

    public function mount()
    {
        abort_unless(auth()->user()->can('estudiantes.ver'), 403);
        
        $activeYear = AnoLectivo::where('activo', true)->first();
        if ($activeYear) {
            $this->filtroAnio = (string) $activeYear->id;
        }
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFiltroGrado() { $this->resetPage(); $this->filtroSeccion = ''; }
    public function updatingFiltroSeccion() { $this->resetPage(); }
    public function updatingFiltroAnio() { $this->resetPage(); }

    public function getEstudiantes()
    {
        return Estudiante::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('nie', 'like', '%' . $this->search . '%')
                      ->orWhere('nombres', 'like', '%' . $this->search . '%')
                      ->orWhere('apellidos', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filtroAnio || $this->filtroGrado || $this->filtroSeccion, function ($query) {
                $query->whereHas('matriculas', function ($q) {
                    $q->when($this->filtroAnio, fn($sub) => $sub->where('ano_lectivo_id', $this->filtroAnio))
                      ->when($this->filtroGrado || $this->filtroSeccion, function ($sub) {
                          $sub->whereHas('seccion', function ($secc) {
                              $secc->when($this->filtroGrado, fn($g) => $g->where('grado_id', $this->filtroGrado))
                                   ->when($this->filtroSeccion, fn($s) => $s->where('id', $this->filtroSeccion));
                          });
                      });
                });
            })
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->paginate(15);
    }

    public function getGrados()
    {
        return Grado::orderBy('orden')->get();
    }

    public function getSecciones()
    {
        if (!$this->filtroGrado) {
            return collect();
        }
        return Seccion::where('grado_id', $this->filtroGrado)
            ->when($this->filtroAnio, fn($q) => $q->where('ano_lectivo_id', $this->filtroAnio))
            ->get();
    }

    public function getAnos()
    {
        return AnoLectivo::orderBy('anio', 'desc')->get();
    }

    public function exportar()
    {
        abort_unless(auth()->user()->can('reportes.caracterizacion'), 403);

        $estudiantes = Estudiante::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('nie', 'like', '%' . $this->search . '%')
                      ->orWhere('nombres', 'like', '%' . $this->search . '%')
                      ->orWhere('apellidos', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filtroAnio || $this->filtroGrado || $this->filtroSeccion, function ($query) {
                $query->whereHas('matriculas', function ($q) {
                    $q->when($this->filtroAnio, fn($sub) => $sub->where('ano_lectivo_id', $this->filtroAnio))
                      ->when($this->filtroGrado || $this->filtroSeccion, function ($sub) {
                          $sub->whereHas('seccion', function ($secc) {
                              $secc->when($this->filtroGrado, fn($g) => $g->where('grado_id', $this->filtroGrado))
                                   ->when($this->filtroSeccion, fn($s) => $s->where('id', $this->filtroSeccion));
                          });
                      });
                });
            })
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        $headers = [
            "Content-type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=estudiantes_caracterizacion.csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($estudiantes) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, ['NIE', 'Apellidos', 'Nombres', 'Género', 'Fecha Nacimiento', 'Repitente', 'Extraedad', 'DAI', 'Convivencia', 'Actividad Económica']);

            foreach ($estudiantes as $estudiante) {
                fputcsv($file, [
                    $estudiante->nie,
                    $estudiante->apellidos,
                    $estudiante->nombres,
                    $estudiante->genero,
                    $estudiante->fecha_nacimiento->format('d/m/Y'),
                    $estudiante->es_repitente ? 'Sí' : 'No',
                    $estudiante->tiene_extraedad ? 'Sí' : 'No',
                    $estudiante->pertenece_dai ? 'Sí' : 'No',
                    $estudiante->convivencia,
                    $estudiante->actividad_economica,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.app')
            ->title('Listado de Estudiantes | SIG');
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Caracterización Estudiantil</h1>
            <p class="mt-1 text-sm text-slate-500">Expediente de estudiantes, caracterización socioeconómica e indicadores académicos.</p>
        </div>
        <div class="flex items-center gap-2">
            @can('reportes.caracterizacion')
            <button 
                wire:click="exportar"
                class="inline-flex items-center gap-2 rounded-xl bg-white hover:bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-700 border border-slate-350 shadow-2xs transition"
            >
                <svg class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Exportar CSV
            </button>
            @endcan
            
            @can('estudiantes.crear')
            <a 
                href="{{ route('estudiantes.crear') }}"
                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:shadow transition"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nuevo Estudiante
            </a>
            @endcan
        </div>
    </div>

    <!-- Filters & Table Card -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs overflow-hidden">
        
        <!-- Filters panel -->
        <div class="p-6 border-b border-slate-100 bg-slate-50/50 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search bar -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.637 10.637z" />
                        </svg>
                    </div>
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="search"
                        placeholder="NIE, nombres o apellidos..." 
                        class="block w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-950 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                    />
                </div>

                <!-- Año Lectivo filter -->
                <div>
                    <select 
                        wire:model.live="filtroAnio"
                        class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-950 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                    >
                        <option value="">Todos los Años...</option>
                        @foreach($this->getAnos() as $ano)
                            <option value="{{ $ano->id }}">{{ $ano->anio }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Grado filter -->
                <div>
                    <select 
                        wire:model.live="filtroGrado"
                        class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-950 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                    >
                        <option value="">Todos los Grados...</option>
                        @foreach($this->getGrados() as $grado)
                            <option value="{{ $grado->id }}">{{ $grado->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Sección filter -->
                <div>
                    <select 
                        wire:model.live="filtroSeccion"
                        @if(!$filtroGrado) disabled @endif
                        class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-950 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition disabled:bg-slate-100 disabled:text-slate-400"
                    >
                        <option value="">Todas las Secciones...</option>
                        @foreach($this->getSecciones() as $secc)
                            <option value="{{ $secc->id }}">Sección {{ $secc->letra }} ({{ $secc->turno }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">NIE</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Estudiante</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Grado / Sección</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Género</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Indicadores</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($this->getEstudiantes() as $estudiante)
                        @php
                            $matriculaActiva = $estudiante->matriculaActiva;
                        @endphp
                        <tr class="hover:bg-slate-50/30 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-700">
                                {{ $estudiante->nie }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-slate-900">{{ $estudiante->nombre_completo }}</div>
                                <div class="text-xs text-slate-500">Nacimiento: {{ $estudiante->fecha_nacimiento->format('d/m/Y') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">
                                @if($matriculaActiva)
                                    <span>{{ $matriculaActiva->seccion->nombre_completo }}</span>
                                @else
                                    <span class="text-slate-400 text-xs italic">No matriculado</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                {{ $estudiante->genero === 'M' ? 'Masculino' : 'Femenino' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-wrap gap-1">
                                    @if($estudiante->es_repitente)
                                        <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/10">Repitente</span>
                                    @endif
                                    @if($estudiante->tiene_extraedad)
                                        <span class="inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/10">Extraedad</span>
                                    @endif
                                    @if($estudiante->pertenece_dai)
                                        <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-600/10">DAI</span>
                                    @endif
                                    @if(!$estudiante->es_repitente && !$estudiante->tiene_extraedad && !$estudiante->pertenece_dai)
                                        <span class="text-xs text-slate-400">-</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    @can('estudiantes.editar')
                                    <a 
                                        href="{{ route('estudiantes.editar', $estudiante->id) }}"
                                        class="p-2 text-slate-500 hover:text-indigo-600 hover:bg-slate-50 rounded-lg transition"
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
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 019.918 5.841 50.58 50.58 0 00-2.658.814m-15.482 0a50.697 50.697 0 0115.482 0m-15.482 0L12 10.062M12 3.493V10.06m0 0L20.25 8" />
                                    </svg>
                                    <p class="mt-2 text-sm font-semibold text-slate-700">No se encontraron estudiantes</p>
                                    <p class="mt-1 text-xs">Intenta cambiar los filtros o el término de búsqueda.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <x-tabla-paginada :items="$this->getEstudiantes()" />
    </div>
</div>
