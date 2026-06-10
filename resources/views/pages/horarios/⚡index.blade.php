<?php

use Livewire\Component;
use App\Models\Seccion;
use App\Models\AnoLectivo;
use App\Models\AsignacionDocente;
use App\Models\HorarioClase;
use Illuminate\Validation\Rule;

new class extends Component
{
    // Filter & Section selected (for admin/director)
    public ?int $seccionId = null;

    // Form fields
    public ?int $asignacionId = null;
    public ?int $diaSemana = null;
    public string $horaInicio = '';
    public string $horaFin = '';

    public function mount()
    {
        abort_unless(auth()->user()->hasRole(['admin', 'director', 'docente']), 403);

        $ano = AnoLectivo::where('activo', true)->first();
        if ($ano) {
            if (auth()->user()->hasRole(['admin', 'director'])) {
                $firstSecc = Seccion::where('ano_lectivo_id', $ano->id)->first();
                if ($firstSecc) {
                    $this->seccionId = $firstSecc->id;
                }
            } else {
                $this->seccionId = null;
            }
        }
    }

    public function updatedSeccionId()
    {
        $this->asignacionId = null;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function getSecciones()
    {
        $ano = AnoLectivo::where('activo', true)->first();
        if (!$ano) {
            return collect();
        }

        return Seccion::where('ano_lectivo_id', $ano->id)->with('grado')->get();
    }

    public function getAsignacionesDeSeccion()
    {
        if (!$this->seccionId) {
            return collect();
        }

        return AsignacionDocente::where('seccion_id', $this->seccionId)
            ->with(['materia', 'personal'])
            ->get();
    }

    public function getHorariosPorDia($diaNum)
    {
        $ano = AnoLectivo::where('activo', true)->first();
        if (!$ano) {
            return collect();
        }

        $query = HorarioClase::where('dia_semana', $diaNum)
            ->with(['asignacionDocente.materia', 'asignacionDocente.seccion', 'asignacionDocente.personal']);

        if (auth()->user()->hasRole(['admin', 'director'])) {
            if (!$this->seccionId) {
                return collect();
            }
            $seccionId = $this->seccionId;
            $query->whereHas('asignacionDocente', function ($q) use ($seccionId, $ano) {
                $q->where('seccion_id', $seccionId)
                  ->where('ano_lectivo_id', $ano->id);
            });
        } else {
            $personal = auth()->user()->personal;
            if (!$personal) {
                return collect();
            }
            $personalId = $personal->id;
            $query->whereHas('asignacionDocente', function ($q) use ($personalId, $ano) {
                $q->where('personal_id', $personalId)
                  ->where('ano_lectivo_id', $ano->id);
            });
        }

        return $query->get()->sortBy('hora_inicio');
    }

    public function programar()
    {
        abort_unless(auth()->user()->hasRole(['admin', 'director']), 403);

        $this->validate([
            'asignacionId' => 'required|exists:asignaciones_docentes,id',
            'diaSemana' => 'required|integer|between:1,5',
            'horaInicio' => 'required|date_format:H:i',
            'horaFin' => 'required|date_format:H:i|after:horaInicio',
        ], [
            'asignacionId.required' => 'Debe seleccionar una materia.',
            'diaSemana.required' => 'Debe seleccionar el día de la semana.',
            'horaInicio.required' => 'Debe ingresar la hora de inicio.',
            'horaFin.required' => 'Debe ingresar la hora de fin.',
            'horaFin.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
        ]);

        $collisionMsg = HorarioClase::hasCollision(
            $this->diaSemana,
            $this->horaInicio . ':00',
            $this->horaFin . ':00',
            $this->asignacionId
        );

        if ($collisionMsg) {
            $this->addError('general', $collisionMsg);
            return;
        }

        HorarioClase::create([
            'asignacion_docente_id' => $this->asignacionId,
            'dia_semana' => $this->diaSemana,
            'hora_inicio' => $this->horaInicio . ':00',
            'hora_fin' => $this->horaFin . ':00',
        ]);

        $this->asignacionId = null;
        $this->diaSemana = null;
        $this->horaInicio = '';
        $this->horaFin = '';
        
        $this->resetErrorBag();
        $this->resetValidation();
        $this->dispatch('notify', message: 'Horario programado con éxito.', type: 'success');
    }

    public function eliminarBlock($id)
    {
        abort_unless(auth()->user()->hasRole(['admin', 'director']), 403);

        $horario = HorarioClase::findOrFail($id);
        $horario->delete();

        $this->dispatch('notify', message: 'Bloque de horario eliminado.', type: 'success');
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.app')
            ->title('Horarios de Clase | SIG');
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Planificación Escolar</span>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900 mt-1">Horarios de Clase</h1>
            <p class="mt-1 text-sm text-slate-500">
                @if(auth()->user()->hasRole(['admin', 'director']))
                    Planifique y visualice los horarios semanales por sección académica evitando choques.
                @else
                    Visualice su horario de clases asignado para el ciclo lectivo activo.
                @endif
            </p>
        </div>
        <div class="flex items-center gap-2 bg-indigo-50 border border-indigo-100 rounded-2xl px-4 py-2 text-indigo-700 text-sm font-semibold">
            <svg class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
            </svg>
            <span>Año Activo: {{ App\Models\AnoLectivo::where('activo', true)->first()?->anio ?? 'Ninguno' }}</span>
        </div>
    </div>

    @if(!App\Models\AnoLectivo::where('activo', true)->first())
        <div class="rounded-3xl border-2 border-dashed border-slate-300 p-12 text-center max-w-lg mx-auto bg-white shadow-2xs">
            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
            <h3 class="mt-4 text-lg font-bold text-slate-900">Requiere Año Lectivo Activo</h3>
            <p class="mt-2 text-sm text-slate-500">Debe registrar y activar un año lectivo en el sistema para programar horarios.</p>
        </div>
    @else
        <!-- Filter Bar (for Admin/Director) -->
        @if(auth()->user()->hasRole(['admin', 'director']))
            <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs p-5">
                <div class="max-w-xs">
                    <label for="form_seccion" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Seleccione una Sección Académica</label>
                    <select 
                        id="form_seccion"
                        wire:model.live="seccionId"
                        class="block w-full rounded-xl border border-slate-350 bg-white px-3 py-2.5 text-slate-900 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition duration-155"
                    >
                        <option value="">Seleccione una sección...</option>
                        @foreach($this->getSecciones() as $secc)
                            <option value="{{ $secc->id }}">{{ $secc->nombre_completo }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endif

        @if(auth()->user()->hasRole(['admin', 'director']) && !$seccionId)
            <div class="rounded-3xl border-2 border-dashed border-slate-300 p-12 text-center max-w-lg mx-auto bg-white shadow-2xs">
                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                <h3 class="mt-4 text-md font-bold text-slate-900">Seleccione una Sección</h3>
                <p class="mt-2 text-xs text-slate-550">Seleccione una sección en la barra superior para ver y configurar su horario de clases.</p>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                
                <!-- Left Column: Add Schedule Entry Form (Only Admin/Director) -->
                @if(auth()->user()->hasRole(['admin', 'director']))
                    <div class="lg:col-span-1 space-y-6">
                        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs p-6 space-y-4">
                            <h2 class="text-lg font-bold text-slate-900 border-b border-slate-100 pb-3">Programar Clase</h2>
                            
                            <form wire:submit.prevent="programar" class="space-y-4">
                                @error('general')
                                    <div class="rounded-2xl border border-rose-100 bg-rose-50 p-4 text-xs font-semibold text-rose-700">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <!-- Materia (Asignación) -->
                                <div>
                                    <label for="form_asignacion" class="block text-sm font-semibold text-slate-700">Materia y Docente</label>
                                    <select 
                                        id="form_asignacion"
                                        wire:model="asignacionId"
                                        class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-955 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                                    >
                                        <option value="">Seleccione una asignatura...</option>
                                        @foreach($this->getAsignacionesDeSeccion() as $asig)
                                            <option value="{{ $asig->id }}">{{ $asig->materia->nombre }} ({{ $asig->personal->nombre_completo }})</option>
                                        @endforeach
                                    </select>
                                    <x-input-error field="asignacionId" />
                                </div>

                                <!-- Día de la Semana -->
                                <div>
                                    <label for="form_dia" class="block text-sm font-semibold text-slate-700">Día de la Semana</label>
                                    <select 
                                        id="form_dia"
                                        wire:model="diaSemana"
                                        class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-955 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                                    >
                                        <option value="">Seleccione un día...</option>
                                        <option value="1">Lunes</option>
                                        <option value="2">Martes</option>
                                        <option value="3">Miércoles</option>
                                        <option value="4">Jueves</option>
                                        <option value="5">Viernes</option>
                                    </select>
                                    <x-input-error field="diaSemana" />
                                </div>

                                <!-- Horarios -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label for="form_hora_inicio" class="block text-xs font-semibold text-slate-700">Hora Inicio</label>
                                        <input 
                                            type="time" 
                                            id="form_hora_inicio"
                                            wire:model="horaInicio"
                                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-955 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                                        />
                                        <x-input-error field="horaInicio" />
                                    </div>
                                    <div>
                                        <label for="form_hora_fin" class="block text-xs font-semibold text-slate-700">Hora Fin</label>
                                        <input 
                                            type="time" 
                                            id="form_hora_fin"
                                            wire:model="horaFin"
                                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-955 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                                        />
                                        <x-input-error field="horaFin" />
                                    </div>
                                </div>

                                <button 
                                    type="submit"
                                    class="w-full flex justify-center rounded-xl bg-indigo-600 hover:bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:shadow transition duration-150"
                                >
                                    Agregar al Horario
                                </button>
                            </form>
                        </div>
                    </div>
                @endif

                <!-- Right Column: Weekly Schedule Grid -->
                <div class="{{ auth()->user()->hasRole(['admin', 'director']) ? 'lg:col-span-3' : 'lg:col-span-4' }}">
                    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs overflow-hidden">
                        <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                            <h2 class="font-bold text-slate-800">
                                @if(auth()->user()->hasRole(['admin', 'director']))
                                    Horario Semanal — {{ App\Models\Seccion::find($seccionId)?->nombre_completo }}
                                @else
                                    Mi Horario Semanal — {{ auth()->user()->personal?->nombre_completo }}
                                @endif
                            </h2>
                        </div>

                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                                @foreach([1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes'] as $diaNum => $diaNombre)
                                    <div class="bg-slate-50/70 rounded-2xl p-4 border border-slate-200/60 shadow-3xs space-y-3">
                                        <h3 class="font-bold text-xs uppercase tracking-wider text-slate-500 border-b border-slate-200 pb-2 text-center">{{ $diaNombre }}</h3>
                                        
                                        <div class="space-y-2.5">
                                            @forelse($this->getHorariosPorDia($diaNum) as $item)
                                                <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-3xs relative group hover:border-indigo-200 transition-all duration-150">
                                                    <div class="text-xs font-bold text-indigo-700 leading-tight">
                                                        {{ $item->asignacionDocente->materia->nombre }}
                                                    </div>
                                                    <div class="text-3xs text-slate-500 font-semibold mt-1">
                                                        {{ $item->asignacionDocente->seccion->nombre_completo }}
                                                    </div>
                                                    <div class="text-3xs text-slate-400 mt-1.5 flex items-center gap-1">
                                                        <svg class="h-3 w-3 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5" />
                                                        </svg>
                                                        <span>{{ substr($item->hora_inicio, 0, 5) }} - {{ substr($item->hora_fin, 0, 5) }}</span>
                                                    </div>
                                                    @if(auth()->user()->hasRole(['admin', 'director']))
                                                        <div class="text-3xs text-slate-450 italic mt-1 leading-normal">
                                                            Docente: {{ $item->asignacionDocente->personal->nombre_completo }}
                                                        </div>
                                                    @endif
                                                    
                                                    @if(auth()->user()->hasRole(['admin', 'director']))
                                                        <button 
                                                            wire:click="eliminarBlock({{ $item->id }})"
                                                            wire:confirm="¿Estás seguro de que deseas eliminar este bloque de horario?"
                                                            class="absolute top-2 right-2 p-1 text-slate-350 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition opacity-0 group-hover:opacity-100"
                                                            title="Eliminar bloque"
                                                        >
                                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    @endif
                                                </div>
                                            @empty
                                                <div class="text-center text-slate-400 text-3xs italic py-4">
                                                    Sin clases
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
