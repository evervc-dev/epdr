<?php

use Livewire\Component;
use App\Models\Seccion;
use App\Models\AnoLectivo;
use App\Models\Matricula;
use App\Models\Asistencia;
use App\Models\AsignacionDocente;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public ?int $asignacionId = null;
    public string $fecha = '';
    public array $estudiantes = [];

    public function mount()
    {
        abort_unless(auth()->user()->hasRole(['admin', 'director', 'docente']), 403);

        $this->fecha = now()->format('Y-m-d');

        $asignaciones = $this->getAsignaciones();
        if ($asignaciones->isNotEmpty()) {
            $this->asignacionId = $asignaciones->first()->id;
        }

        $this->cargarEstudiantes();
    }

    public function getAsignaciones()
    {
        $ano = AnoLectivo::where('activo', true)->first();
        if (!$ano) {
            return collect();
        }

        $query = AsignacionDocente::where('ano_lectivo_id', $ano->id)
            ->with(['materia.grado', 'seccion.grado', 'personal']);

        if (!auth()->user()->hasRole(['admin', 'director'])) {
            $query->delDocenteActual();
        }

        return $query->get()->sortBy(function($a) {
            return $a->seccion->grado->orden . '-' . $a->seccion->letra . '-' . $a->materia->nombre;
        });
    }

    public function updatedAsignacionId()
    {
        $this->cargarEstudiantes();
    }

    public function updatedFecha()
    {
        $this->cargarEstudiantes();
    }

    public function cargarEstudiantes()
    {
        $this->estudiantes = [];

        if (!$this->asignacionId || !$this->fecha) {
            return;
        }

        $asignacion = AsignacionDocente::with(['seccion', 'materia'])->findOrFail($this->asignacionId);

        // Security check for docentes
        if (!auth()->user()->hasRole(['admin', 'director'])) {
            $personal = auth()->user()->personal;
            if (!$personal || $asignacion->personal_id !== $personal->id) {
                $this->asignacionId = null;
                return;
            }
        }

        $matriculas = Matricula::where('seccion_id', $asignacion->seccion_id)
            ->where('estado', 'ACTIVA')
            ->with('estudiante')
            ->get()
            ->sortBy(fn($m) => $m->estudiante->apellidos . ' ' . $m->estudiante->nombres);

        $fechaObj = \Illuminate\Support\Carbon::parse($this->fecha)->startOfDay();

        $existentes = Asistencia::where('materia_id', $asignacion->materia_id)
            ->whereIn('matricula_id', $matriculas->pluck('id'))
            ->where('fecha', $fechaObj)
            ->get()
            ->keyBy('matricula_id');

        foreach ($matriculas as $mat) {
            $ex = $existentes->get($mat->id);

            $this->estudiantes[] = [
                'matricula_id' => $mat->id,
                'nie' => $mat->estudiante->nie,
                'nombre' => $mat->estudiante->nombre_completo,
                'estado' => $ex ? $ex->estado : 'P',
                'observacion' => $ex ? $ex->observacion : '',
            ];
        }
    }

    public function marcarTodos($estado)
    {
        if (!in_array($estado, ['P', 'A', 'J'])) {
            return;
        }

        foreach ($this->estudiantes as $key => $est) {
            $this->estudiantes[$key]['estado'] = $estado;
        }
    }

    public function guardar()
    {
        abort_unless(auth()->user()->hasRole(['admin', 'director', 'docente']), 403);

        if (!$this->asignacionId || !$this->fecha) {
            return;
        }

        $asignacion = AsignacionDocente::findOrFail($this->asignacionId);

        if (!auth()->user()->hasRole(['admin', 'director'])) {
            $personal = auth()->user()->personal;
            if (!$personal || $asignacion->personal_id !== $personal->id) {
                $this->dispatch('notify', message: 'No tiene permiso para registrar asistencia en esta materia.', type: 'error');
                return;
            }
        }

        $fechaObj = \Illuminate\Support\Carbon::parse($this->fecha)->startOfDay();

        DB::transaction(function () use ($asignacion, $fechaObj) {
            foreach ($this->estudiantes as $est) {
                $record = Asistencia::firstOrNew([
                    'matricula_id' => $est['matricula_id'],
                    'materia_id' => $asignacion->materia_id,
                    'fecha' => $fechaObj,
                ]);

                $record->fill([
                    'estado' => $est['estado'],
                    'observacion' => $est['observacion'] ?: null,
                    'registrado_por' => auth()->id(),
                ]);

                $record->save();
            }
        });

        $this->dispatch('notify', message: 'Asistencia guardada con éxito.', type: 'success');
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.app')
            ->title('Registro de Asistencia | SIG');
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Control de Asistencia</h1>
            <p class="mt-1 text-sm text-slate-500">Registre la asistencia de los estudiantes por materia y sección.</p>
        </div>
        <div>
            @if($asignacionId && count($estudiantes) > 0)
                <button 
                    type="button"
                    wire:click="guardar"
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:shadow transition duration-150"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Guardar Asistencia
                </button>
            @endif
        </div>
    </div>

    <!-- Filters and Helpers -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs p-5 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Asignación Select -->
            <div>
                <label for="form_asignacion" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Materia / Sección</label>
                <select 
                    id="form_asignacion"
                    wire:model.live="asignacionId"
                    class="block w-full rounded-xl border border-slate-350 bg-white px-3 py-2.5 text-slate-900 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition duration-155"
                >
                    <option value="">Seleccione una asignatura...</option>
                    @foreach($this->getAsignaciones() as $asig)
                        <option value="{{ $asig->id }}">{{ $asig->materia->nombre }} — {{ $asig->seccion->nombre_completo }} @if(auth()->user()->hasRole(['admin', 'director'])) ({{ $asig->personal->nombre_completo }}) @endif</option>
                    @endforeach
                </select>
            </div>

            <!-- Fecha input -->
            <div>
                <label for="form_fecha" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Fecha del Control</label>
                <input 
                    type="date" 
                    id="form_fecha"
                    wire:model.live="fecha"
                    class="block w-full rounded-xl border border-slate-350 bg-white px-3 py-2.5 text-slate-900 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition duration-155"
                />
            </div>

            <!-- Quick markers helpers -->
            @if($asignacionId && count($estudiantes) > 0)
                <div class="flex flex-col justify-end pb-1.5 space-y-1">
                    <span class="block text-2xs font-bold text-slate-400 uppercase tracking-wider">Acciones rápidas</span>
                    <div class="flex items-center gap-2">
                        <button 
                            type="button"
                            wire:click="marcarTodos('P')"
                            class="inline-flex items-center rounded-lg bg-emerald-50 hover:bg-emerald-100 px-3 py-1.5 text-xs font-bold text-emerald-700 transition"
                        >
                            Todos Presentes
                        </button>
                        <button 
                            type="button"
                            wire:click="marcarTodos('A')"
                            class="inline-flex items-center rounded-lg bg-rose-50 hover:bg-rose-100 px-3 py-1.5 text-xs font-bold text-rose-700 transition"
                        >
                            Todos Ausentes
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Attendance Grid List -->
    @if($asignacionId)
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100 text-xs font-bold text-slate-500 uppercase tracking-wider">
                            <th class="px-6 py-4 w-72">Estudiante</th>
                            <th class="px-6 py-4 w-72 text-center">Estado de Asistencia</th>
                            <th class="px-6 py-4">Observaciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($estudiantes as $key => $est)
                            <tr class="hover:bg-slate-50/15 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-slate-900">{{ $est['nombre'] }}</div>
                                    <div class="text-xs text-slate-450">NIE: {{ $est['nie'] }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="inline-flex rounded-xl border border-slate-200 p-1 bg-slate-50 gap-1">
                                        <button 
                                            type="button"
                                            wire:click="$set('estudiantes.{{ $key }}.estado', 'P')"
                                            class="px-4 py-1.5 text-xs font-bold rounded-lg transition {{ $est['estado'] === 'P' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}"
                                        >
                                            Presente
                                        </button>
                                        <button 
                                            type="button"
                                            wire:click="$set('estudiantes.{{ $key }}.estado', 'A')"
                                            class="px-4 py-1.5 text-xs font-bold rounded-lg transition {{ $est['estado'] === 'A' ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}"
                                        >
                                            Ausente
                                        </button>
                                        <button 
                                            type="button"
                                            wire:click="$set('estudiantes.{{ $key }}.estado', 'J')"
                                            class="px-4 py-1.5 text-xs font-bold rounded-lg transition {{ $est['estado'] === 'J' ? 'bg-amber-500 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}"
                                        >
                                            Justificado
                                        </button>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <input 
                                        type="text" 
                                        wire:model="estudiantes.{{ $key }}.observacion"
                                        placeholder="Ej: Retraso justificado, cita médica..."
                                        class="w-full rounded-xl border border-slate-350 px-4 py-2 text-sm focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 text-slate-900 transition"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-10 text-center text-slate-400 text-sm italic">
                                    No se encontraron estudiantes activos en esta sección.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
