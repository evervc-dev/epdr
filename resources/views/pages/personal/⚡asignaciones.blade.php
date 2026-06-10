<?php

use Livewire\Component;
use App\Models\Personal;
use App\Models\Materia;
use App\Models\Seccion;
use App\Models\AnoLectivo;
use App\Models\AsignacionDocente;
use Illuminate\Validation\Rule;

new class extends Component
{
    public int $personalId;
    public ?int $materiaId = null;
    public ?int $seccionId = null;

    public function mount($id)
    {
        abort_unless(auth()->user()->can('asignaciones.gestionar'), 403);
        
        $persona = Personal::findOrFail($id);
        
        if ($persona->tipo !== 'docente') {
            session()->flash('error', 'El personal seleccionado no es docente.');
            return redirect()->route('personal.index');
        }

        $this->personalId = $persona->id;
    }

    public function getPersona()
    {
        return Personal::findOrFail($this->personalId);
    }

    public function getAnioActivo()
    {
        return AnoLectivo::where('activo', true)->first();
    }

    public function getAsignaciones()
    {
        $ano = $this->getAnioActivo();
        if (!$ano) {
            return collect();
        }

        return AsignacionDocente::where('personal_id', $this->personalId)
            ->where('ano_lectivo_id', $ano->id)
            ->with(['materia.grado', 'seccion'])
            ->get();
    }

    public function getMaterias()
    {
        return Materia::with('grado')->get()->sortBy('grado.orden');
    }

    public function getSecciones()
    {
        $ano = $this->getAnioActivo();
        if (!$ano) {
            return collect();
        }

        return Seccion::where('ano_lectivo_id', $ano->id)
            ->with('grado')
            ->get();
    }

    public function asignar()
    {
        abort_unless(auth()->user()->can('asignaciones.gestionar'), 403);

        $ano = $this->getAnioActivo();
        if (!$ano) {
            $this->dispatch('notify', message: 'No hay un año lectivo activo.', type: 'error');
            return;
        }

        $this->validate([
            'materiaId' => 'required|exists:materias,id',
            'seccionId' => 'required|exists:secciones,id',
        ], [
            'materiaId.required' => 'Debe seleccionar una materia.',
            'seccionId.required' => 'Debe seleccionar una sección.',
        ]);

        $materia = Materia::findOrFail($this->materiaId);
        $seccion = Seccion::findOrFail($this->seccionId);
        
        if ($materia->grado_id !== $seccion->grado_id) {
            $this->addError('materiaId', 'La materia y la sección seleccionadas deben pertenecer al mismo grado escolar.');
            return;
        }

        $alreadyAssigned = AsignacionDocente::where('materia_id', $this->materiaId)
            ->where('seccion_id', $this->seccionId)
            ->where('ano_lectivo_id', $ano->id)
            ->first();

        if ($alreadyAssigned) {
            if ($alreadyAssigned->personal_id === $this->personalId) {
                $this->dispatch('notify', message: 'Esta materia ya está asignada a este docente.', type: 'warning');
            } else {
                $this->addError('materiaId', 'Esta materia ya está asignada al docente ' . $alreadyAssigned->personal->nombre_completo . ' en esta sección.');
            }
            return;
        }

        AsignacionDocente::create([
            'personal_id' => $this->personalId,
            'materia_id' => $this->materiaId,
            'seccion_id' => $this->seccionId,
            'ano_lectivo_id' => $ano->id,
        ]);

        $this->materiaId = null;
        $this->seccionId = null;
        $this->dispatch('notify', message: 'Asignación docente creada con éxito.', type: 'success');
    }

    public function eliminar($id)
    {
        abort_unless(auth()->user()->can('asignaciones.gestionar'), 403);
        
        $asignacion = AsignacionDocente::findOrFail($id);
        
        if ($asignacion->registroNotas()->exists()) {
            $this->dispatch('notify', message: 'No se puede eliminar la asignación porque ya existen notas registradas.', type: 'error');
            return;
        }

        $asignacion->delete();
        $this->dispatch('notify', message: 'Asignación docente eliminada con éxito.', type: 'success');
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.app')
            ->title('Asignaciones del Docente | SIG');
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Asignación Académica</span>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900 mt-1">
                {{ $this->getPersona()->nombre_completo }}
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                Especialidad: {{ $this->getPersona()->especialidad ?: 'No registrada' }}
            </p>
        </div>
        <div>
            <a 
                href="{{ route('personal.index') }}"
                class="inline-flex items-center gap-2 rounded-xl bg-white hover:bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-700 border border-slate-300 shadow-2xs transition"
            >
                Volver al Personal
            </a>
        </div>
    </div>

    @if(!$this->getAnioActivo())
        <div class="rounded-3xl border-2 border-dashed border-slate-300 p-12 text-center max-w-lg mx-auto bg-white shadow-2xs">
            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
            <h3 class="mt-4 text-lg font-bold text-slate-900">Requiere Año Lectivo Activo</h3>
            <p class="mt-2 text-sm text-slate-500">Para asignar materias y secciones a un docente, primero debe registrar y activar un año lectivo.</p>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left: Add Assignment Form -->
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs p-6 space-y-4">
                    <h2 class="text-lg font-bold text-slate-900 border-b border-slate-100 pb-3">Nueva Asignación</h2>
                    
                    <form wire:submit.prevent="asignar" class="space-y-4">
                        <!-- Materia -->
                        <div>
                            <label for="form_materia" class="block text-sm font-semibold text-slate-700">Materia / Asignatura</label>
                            <select 
                                id="form_materia"
                                wire:model="materiaId"
                                class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-955 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                            >
                                <option value="">Seleccione una materia...</option>
                                @foreach($this->getMaterias() as $mat)
                                    <option value="{{ $mat->id }}">{{ $mat->nombre }} ({{ $mat->grado->nombre }})</option>
                                @endforeach
                            </select>
                            <x-input-error field="materiaId" />
                        </div>

                        <!-- Sección -->
                        <div>
                            <label for="form_seccion" class="block text-sm font-semibold text-slate-700">Sección Académica</label>
                            <select 
                                id="form_seccion"
                                wire:model="seccionId"
                                class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-955 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                            >
                                <option value="">Seleccione una sección...</option>
                                @foreach($this->getSecciones() as $secc)
                                    <option value="{{ $secc->id }}">{{ $secc->nombre_completo }}</option>
                                @endforeach
                            </select>
                            <x-input-error field="seccionId" />
                        </div>

                        <button 
                            type="submit"
                            class="w-full flex justify-center rounded-xl bg-indigo-600 hover:bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:shadow transition duration-150"
                        >
                            Asignar al Docente
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right: Current Assignments list -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs overflow-hidden">
                    <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                        <h2 class="font-bold text-slate-800">Carga Académica Activa</h2>
                        <span class="text-xs text-indigo-600 font-semibold bg-indigo-50 px-2.5 py-1 rounded-full">
                            Año Lectivo: {{ $this->getAnioActivo()->anio }}
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/50 border-b border-slate-100">
                                    <th class="px-5 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Materia</th>
                                    <th class="px-5 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Grado Escolar</th>
                                    <th class="px-5 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Sección</th>
                                    <th class="px-5 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($this->getAsignaciones() as $asig)
                                    <tr class="hover:bg-slate-50/20 transition-colors">
                                        <td class="px-5 py-4 whitespace-nowrap text-sm font-bold text-slate-900">
                                            {{ $asig->materia->nombre }}
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap text-sm text-slate-650">
                                            {{ $asig->materia->grado->nombre }}
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap text-sm text-slate-600">
                                            Sección "{{ $asig->seccion->letra }}" ({{ $asig->seccion->turno }})
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button 
                                                wire:click="eliminar({{ $asig->id }})"
                                                wire:confirm="¿Estás seguro de que deseas eliminar esta asignación? Esta acción desvinculará al docente de la materia y sección."
                                                class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                                                title="Remover Asignación"
                                            >
                                                <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-5 py-8 text-center text-slate-400 text-xs italic">
                                            El docente no posee asignaciones académicas registradas para este periodo.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
