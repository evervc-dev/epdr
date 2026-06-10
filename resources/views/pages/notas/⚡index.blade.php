<?php

use Livewire\Component;
use App\Models\AsignacionDocente;
use App\Models\AnoLectivo;

new class extends Component
{
    public int $trimestre = 1;

    public function mount()
    {
        abort_unless(auth()->user()->hasRole(['admin', 'director', 'docente']), 403);
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

        $query = AsignacionDocente::where('ano_lectivo_id', $ano->id)
            ->with(['personal', 'materia.grado', 'seccion.grado']);

        if (!auth()->user()->hasRole(['admin', 'director'])) {
            $query->delDocenteActual();
        }

        return $query->get()->sortBy(function($a) {
            return $a->seccion->grado->orden . '-' . $a->seccion->letra . '-' . $a->materia->nombre;
        });
    }

    public function irARegistro($asignacionId)
    {
        return redirect()->route('notas.registro', [
            'asignacion' => $asignacionId,
            'trimestre' => $this->trimestre
        ]);
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.app')
            ->title('Registro de Calificaciones | SIG');
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Registro de Calificaciones</h1>
            <p class="mt-1 text-sm text-slate-500">Seleccione el trimestre y la asignatura para ingresar o modificar notas.</p>
        </div>
        <div class="flex items-center gap-2 bg-indigo-50 border border-indigo-100 rounded-2xl px-4 py-2 text-indigo-700 text-sm font-semibold">
            <svg class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
            </svg>
            <span>Año Activo: {{ $this->getAnioActivo()?->anio ?? 'Ninguno' }}</span>
        </div>
    </div>

    @if(!$this->getAnioActivo())
        <div class="rounded-3xl border-2 border-dashed border-slate-300 p-12 text-center max-w-lg mx-auto bg-white shadow-2xs">
            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
            <h3 class="mt-4 text-lg font-bold text-slate-900">Requiere Año Lectivo Activo</h3>
            <p class="mt-2 text-sm text-slate-500">Para poder ingresar notas, debe haber un año lectivo activo en el sistema.</p>
        </div>
    @else
        <!-- Selection and assignments layout -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            
            <!-- Left: Select Trimestre Card -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs p-6 space-y-4">
                    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Período de Calificación</h2>
                    
                    <div class="space-y-2.5">
                        <label class="relative flex items-center gap-3 rounded-xl border border-slate-200 p-4 cursor-pointer hover:bg-slate-50 transition {{ $trimestre === 1 ? 'border-indigo-600 bg-indigo-50/10' : '' }}">
                            <input type="radio" wire:model.live="trimestre" value="1" class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                            <div class="text-xs">
                                <span class="font-bold text-slate-900">Trimestre 1</span>
                                <p class="text-slate-550 text-3xs mt-0.5">Inicio de ciclo</p>
                            </div>
                        </label>

                        <label class="relative flex items-center gap-3 rounded-xl border border-slate-200 p-4 cursor-pointer hover:bg-slate-50 transition {{ $trimestre === 2 ? 'border-indigo-600 bg-indigo-50/10' : '' }}">
                            <input type="radio" wire:model.live="trimestre" value="2" class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                            <div class="text-xs">
                                <span class="font-bold text-slate-900">Trimestre 2</span>
                                <p class="text-slate-550 text-3xs mt-0.5">Evaluaciones medias</p>
                            </div>
                        </label>

                        <label class="relative flex items-center gap-3 rounded-xl border border-slate-200 p-4 cursor-pointer hover:bg-slate-50 transition {{ $trimestre === 3 ? 'border-indigo-600 bg-indigo-50/10' : '' }}">
                            <input type="radio" wire:model.live="trimestre" value="3" class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                            <div class="text-xs">
                                <span class="font-bold text-slate-900">Trimestre 3</span>
                                <p class="text-slate-550 text-3xs mt-0.5">Fin de ciclo escolar</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Right: List of Docente Assignments -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs overflow-hidden">
                    <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                        <h2 class="font-bold text-slate-800">Cursos y Asignaciones Disponibles</h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/50 border-b border-slate-100">
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Materia</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Grado / Sección</th>
                                    @if(auth()->user()->hasRole(['admin', 'director']))
                                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Docente</th>
                                    @endif
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($this->getAsignaciones() as $asig)
                                    <tr class="hover:bg-slate-50/30 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900">
                                            {{ $asig->materia->nombre }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">
                                            {{ $asig->seccion->nombre_completo }}
                                        </td>
                                        @if(auth()->user()->hasRole(['admin', 'director']))
                                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600 font-medium">
                                                {{ $asig->personal->nombre_completo }}
                                            </td>
                                        @endif
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button 
                                                wire:click="irARegistro({{ $asig->id }})"
                                                class="inline-flex items-center gap-1 text-xs font-bold bg-indigo-600 text-white hover:bg-indigo-500 px-3.5 py-2 rounded-xl transition"
                                            >
                                                Ingresar Notas
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-10 text-center text-slate-400 text-xs italic">
                                            No se encontraron asignaciones académicas para ingresar calificaciones.
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
