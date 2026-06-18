<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Matricula;
use App\Models\Estudiante;
use App\Models\Seccion;
use App\Models\AnoLectivo;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    use WithPagination;

    public ?int $anioLectivoId = null;
    public ?int $seccionId = null;
    public string $estudianteSearch = '';

    protected $queryString = [
        'anioLectivoId' => ['except' => null],
        'seccionId' => ['except' => null],
    ];

    public function mount()
    {
        abort_unless(auth()->user()->can('matriculas.gestionar'), 403);

        $activeYear = AnoLectivo::where('activo', true)->first();
        if ($activeYear) {
            $this->anioLectivoId = $activeYear->id;
        }

        if ($this->anioLectivoId) {
            $firstSeccion = Seccion::where('ano_lectivo_id', $this->anioLectivoId)->first();
            if ($firstSeccion) {
                $this->seccionId = $firstSeccion->id;
            }
        }
    }

    public function updatingAnioLectivoId()
    {
        $this->seccionId = null;
        $this->resetPage();
    }

    public function updatingSeccionId()
    {
        $this->resetPage();
    }

    public function getAnos()
    {
        return AnoLectivo::orderBy('anio', 'desc')->get();
    }

    public function getSecciones()
    {
        if (!$this->anioLectivoId) {
            return collect();
        }
        return Seccion::where('ano_lectivo_id', $this->anioLectivoId)
            ->with('grado')
            ->get();
    }

    public function getMatriculas()
    {
        if (!$this->seccionId) {
            return collect();
        }

        return Matricula::where('seccion_id', $this->seccionId)
            ->with('estudiante')
            ->latest()
            ->paginate(15);
    }

    public function getEstudiantesBusqueda()
    {
        if (strlen($this->estudianteSearch) < 2) {
            return collect();
        }

        return Estudiante::query()
            ->where(function ($q) {
                $q->where('nie', 'like', '%' . $this->estudianteSearch . '%')
                  ->orWhere('nombres', 'like', '%' . $this->estudianteSearch . '%')
                  ->orWhere('apellidos', 'like', '%' . $this->estudianteSearch . '%');
            })
            ->take(5)
            ->get();
    }

    public function matricular($estudianteId, $tipoInscripcion = 'N')
    {
        abort_unless(auth()->user()->can('matriculas.gestionar'), 403);

        if (!$this->seccionId || !$this->anioLectivoId) {
            $this->dispatch('notify', message: 'Debe seleccionar un año lectivo y una sección.', type: 'error');
            return;
        }

        $estudiante = Estudiante::findOrFail($estudianteId);

        $existingMatricula = Matricula::where('estudiante_id', $estudianteId)
            ->where('ano_lectivo_id', $this->anioLectivoId)
            ->where('estado', 'ACTIVA')
            ->first();

        if ($existingMatricula) {
            if ($existingMatricula->seccion_id === $this->seccionId) {
                $this->dispatch('notify', message: 'El estudiante ya está matriculado en esta sección.', type: 'warning');
                return;
            }

            DB::transaction(function () use ($existingMatricula, $estudianteId) {
                $existingMatricula->update(['estado' => 'TRASLADADA']);

                Matricula::create([
                    'estudiante_id' => $estudianteId,
                    'seccion_id' => $this->seccionId,
                    'ano_lectivo_id' => $this->anioLectivoId,
                    'tipo_inscripcion' => 'T',
                    'fecha_matricula' => now()->format('Y-m-d'),
                    'estado' => 'ACTIVA',
                ]);
            });

            $this->estudianteSearch = '';
            $this->dispatch('notify', message: 'Estudiante trasladado con éxito a esta sección.', type: 'success');
            return;
        }

        Matricula::create([
            'estudiante_id' => $estudianteId,
            'seccion_id' => $this->seccionId,
            'ano_lectivo_id' => $this->anioLectivoId,
            'tipo_inscripcion' => $tipoInscripcion,
            'fecha_matricula' => now()->format('Y-m-d'),
            'estado' => 'ACTIVA',
        ]);

        $this->estudianteSearch = '';
        $this->dispatch('notify', message: 'Estudiante matriculado con éxito.', type: 'success');
    }

    public function cambiarEstado($matriculaId, $estado)
    {
        abort_unless(auth()->user()->can('matriculas.gestionar'), 403);

        $matricula = Matricula::findOrFail($matriculaId);
        
        if (!in_array($estado, ['ACTIVA', 'RETIRADA', 'TRASLADADA'])) {
            return;
        }

        if ($estado === 'ACTIVA') {
            $alreadyActive = Matricula::where('estudiante_id', $matricula->estudiante_id)
                ->where('ano_lectivo_id', $matricula->ano_lectivo_id)
                ->where('estado', 'ACTIVA')
                ->where('id', '!=', $matricula->id)
                ->with('seccion')
                ->first();

            if ($alreadyActive) {
                $this->dispatch('notify', message: "El estudiante ya tiene una matrícula ACTIVA en la sección {$alreadyActive->seccion->nombre_completo}.", type: 'error');
                return;
            }
        }

        $matricula->update(['estado' => $estado]);

        $this->dispatch('notify', message: 'Estado de matrícula actualizado con éxito.', type: 'success');
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.app')
            ->title('Gestión de Matrículas | SIG');
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Gestión de Matrículas</h1>
            <p class="mt-1 text-sm text-slate-500">Inscripción de estudiantes en grados y secciones para el año lectivo actual.</p>
        </div>
    </div>

    <!-- Selection Bar -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Año Lectivo filter -->
        <div>
            <label for="form_anio" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Año Lectivo</label>
            <select 
                id="form_anio"
                wire:model.live="anioLectivoId"
                class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-950 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
            >
                <option value="">Seleccione un año...</option>
                @foreach($this->getAnos() as $ano)
                    <option value="{{ $ano->id }}">{{ $ano->anio }}</option>
                @endforeach
            </select>
        </div>

        <!-- Sección filter -->
        <div>
            <label for="form_seccion" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Sección Académica</label>
            <select 
                id="form_seccion"
                wire:model.live="seccionId"
                @if(!$anioLectivoId) disabled @endif
                class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-950 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition disabled:bg-slate-100"
            >
                <option value="">Seleccione una sección...</option>
                @foreach($this->getSecciones() as $secc)
                    <option value="{{ $secc->id }}">{{ $secc->nombre_completo }} ({{ $secc->turno }})</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Main Workspace -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left: Student search & Quick Enroll -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs p-6 space-y-4">
                <h2 class="text-lg font-bold text-slate-900 border-b border-slate-100 pb-3">Matricular Estudiante</h2>
                
                @if(!$seccionId)
                    <p class="text-xs text-slate-400 italic">Seleccione un año lectivo y una sección para habilitar la matrícula.</p>
                @else
                    <!-- Search input -->
                    <div class="space-y-2">
                        <label for="search_estudiante" class="block text-sm font-semibold text-slate-700">Buscar Estudiante</label>
                        <div class="relative">
                            <input 
                                type="text" 
                                id="search_estudiante"
                                wire:model.live.debounce.250ms="estudianteSearch"
                                placeholder="Escriba NIE o nombre..." 
                                class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-950 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                            />
                        </div>
                    </div>

                    <!-- Search Results -->
                    <div class="space-y-2">
                        @if(strlen($estudianteSearch) >= 2)
                            <div class="border border-slate-200 rounded-2xl overflow-hidden divide-y divide-slate-100 bg-slate-50/40">
                                @forelse($this->getEstudiantesBusqueda() as $est)
                                    @php
                                        $currentMat = $est->matriculaActiva;
                                    @endphp
                                    <div class="p-3 flex items-center justify-between gap-2 text-xs">
                                        <div>
                                            <div class="font-bold text-slate-800">{{ $est->nombre_completo }}</div>
                                            <div class="text-slate-400 text-3xs">NIE: {{ $est->nie }}</div>
                                            @if($currentMat)
                                                <div class="text-indigo-600 font-semibold text-3xs">Ya matriculado en {{ $currentMat->seccion->nombre_completo }}</div>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-1 shrink-0">
                                            @if($currentMat)
                                                <button 
                                                    type="button"
                                                    wire:click="matricular({{ $est->id }})"
                                                    wire:confirm="El estudiante ya tiene una matrícula activa en este año escolar. ¿Desea realizar un traslado de sección?"
                                                    class="rounded-lg bg-indigo-50 text-indigo-700 font-bold px-2 py-1 hover:bg-indigo-100 transition"
                                                >
                                                    Trasladar
                                                </button>
                                            @else
                                                <button 
                                                    type="button"
                                                    wire:click="matricular({{ $est->id }}, 'N')"
                                                    class="rounded-lg bg-indigo-600 text-white font-bold px-2 py-1 hover:bg-indigo-500 transition"
                                                >
                                                    Inscribir
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-4 text-center text-slate-400 italic text-xs">
                                        No se encontraron estudiantes para esa búsqueda.
                                    </div>
                                @endforelse
                            </div>
                        @elseif(strlen($estudianteSearch) > 0)
                            <p class="text-3xs text-slate-400 italic">Escriba al menos 2 caracteres para buscar...</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- Right: Enrolled list in current section -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="font-bold text-slate-800">Alumnos Inscritos en la Sección</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 border-b border-slate-100">
                                <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Estudiante</th>
                                <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Fecha Inscripción</th>
                                <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Tipo</th>
                                <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Estado</th>
                                <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($this->getMatriculas() as $mat)
                                <tr class="hover:bg-slate-50/20 transition-colors">
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-slate-900">{{ $mat->estudiante->nombre_completo }}</div>
                                        <div class="text-2xs text-slate-400">NIE: {{ $mat->estudiante->nie }}</div>
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap text-xs text-slate-500">
                                        {{ $mat->fecha_matricula->format('d/m/Y') }}
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap text-xs">
                                        @if($mat->tipo_inscripcion === 'N')
                                            <span class="inline-flex items-center rounded-md bg-sky-50 px-1.5 py-0.5 text-xs font-medium text-sky-700 ring-1 ring-inset ring-sky-600/10">Nuevo</span>
                                        @elseif($mat->tipo_inscripcion === 'T')
                                            <span class="inline-flex items-center rounded-md bg-indigo-50 px-1.5 py-0.5 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-600/10">Traslado</span>
                                        @else
                                            <span class="inline-flex items-center rounded-md bg-slate-50 px-1.5 py-0.5 text-xs font-medium text-slate-700 ring-1 ring-inset ring-slate-600/10">Variable</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap text-xs">
                                        @if($mat->estado === 'ACTIVA')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/10">Activa</span>
                                        @elseif($mat->estado === 'RETIRADA')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/10">Retirada</span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/10">Trasladada</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap text-right text-sm font-medium">
                                        @if($mat->estado === 'ACTIVA')
                                            <div class="flex items-center justify-end gap-1">
                                                <button 
                                                    wire:click="cambiarEstado({{ $mat->id }}, 'RETIRADA')"
                                                    wire:confirm="¿Desea registrar el retiro de este estudiante? Esto cancelará su matrícula activa."
                                                    class="inline-flex items-center text-xs font-semibold text-rose-600 hover:text-rose-500 hover:bg-rose-50 px-2 py-1 rounded-md transition"
                                                >
                                                    Retirar
                                                </button>
                                            </div>
                                        @else
                                            <button 
                                                wire:click="cambiarEstado({{ $mat->id }}, 'ACTIVA')"
                                                class="inline-flex items-center text-xs font-semibold text-indigo-600 hover:text-indigo-500 hover:bg-indigo-50 px-2 py-1 rounded-md transition"
                                            >
                                                Re-activar
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-slate-400 text-xs italic">
                                        No hay estudiantes matriculados en esta sección para el periodo seleccionado.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($seccionId)
                    <x-tabla-paginada :items="$this->getMatriculas()" />
                @endif
            </div>
        </div>
    </div>
</div>
