<?php

use App\Models\AnoLectivo;
use App\Models\AsignacionDocente;
use App\Models\Matricula;
use App\Models\RegistroNota;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    public int $asignacionId;
    public int $trimestre;
    
    public array $registros = [];

    public function mount($asignacion, $trimestre)
    {
        $this->trimestre = intval($trimestre);
        
        $asig = AsignacionDocente::findOrFail($asignacion);
        $this->asignacionId = $asig->id;

        if (!auth()->user()->hasRole(['admin', 'director'])) {
            $personal = auth()->user()->personal;
            if (!$personal || $asig->personal_id !== $personal->id) {
                abort(403, 'No tiene permiso para acceder a esta asignación.');
            }
        }

        $matriculas = Matricula::where('seccion_id', $asig->seccion_id)
            ->where('ano_lectivo_id', $asig->ano_lectivo_id)
            ->where('estado', 'ACTIVA')
            ->whereHas('estudiante')
            ->get()
            ->sortBy(fn($m) => $m->estudiante->apellidos . ' ' . $m->estudiante->nombres);

        $existentes = RegistroNota::where('asignacion_docente_id', $asig->id)
            ->where('trimestre', $this->trimestre)
            ->get()
            ->keyBy('matricula_id');

        foreach ($matriculas as $mat) {
            $ex = $existentes->get($mat->id);

            $this->registros[$mat->id] = [
                'matricula_id' => $mat->id,
                'nie' => $mat->estudiante->nie,
                'nombre' => $mat->estudiante->nombre_completo,
                'act1' => $ex ? $ex->act1 : '',
                'act2' => $ex ? $ex->act2 : '',
                'act3' => $ex ? $ex->act3 : '',
                'act4' => $ex ? $ex->act4 : '',
                'act5' => $ex ? $ex->act5 : '',
                'act6' => $ex ? $ex->act6 : '',
                'act7' => $ex ? $ex->act7 : '',
                'rev_cuaderno' => $ex ? $ex->rev_cuaderno : '',
                'prueba1' => $ex ? $ex->prueba1 : '',
                'prueba2' => $ex ? $ex->prueba2 : '',
                'observaciones' => $ex ? $ex->observaciones : '',
            ];
        }
    }

    public function getAsignacion()
    {
        return AsignacionDocente::with(['personal', 'materia.grado', 'seccion.grado'])->find($this->asignacionId);
    }

    public function calcularNotaPrevia($matriculaId)
    {
        $reg = $this->registros[$matriculaId] ?? [];
        
        $actividades = collect([
            $reg['act1'] ?? null,
            $reg['act2'] ?? null,
            $reg['act3'] ?? null,
            $reg['act4'] ?? null,
            $reg['act5'] ?? null,
            $reg['act6'] ?? null,
            $reg['act7'] ?? null,
            $reg['rev_cuaderno'] ?? null,
        ])->filter(fn($val) => !is_null($val) && $val !== '');

        $avgActividades = $actividades->isEmpty() ? 0 : $actividades->avg();

        $p1 = ($reg['prueba1'] === '' || is_null($reg['prueba1'])) ? 0 : floatval($reg['prueba1']);
        $p2 = ($reg['prueba2'] === '' || is_null($reg['prueba2'])) ? 0 : floatval($reg['prueba2']);

        return round(
            ($avgActividades * 0.35) + ($p1 * 0.30) + ($p2 * 0.35),
            2
        );
    }

    public function guardar()
    {
        $asig = $this->getAsignacion();
        
        if (!auth()->user()->hasRole(['admin', 'director'])) {
            $personal = auth()->user()->personal;
            if (!$personal || $asig->personal_id !== $personal->id) {
                abort(403, 'No autorizado.');
            }
        }

        $rules = [];
        $messages = [];
        
        foreach ($this->registros as $matId => $reg) {
            foreach (['act1', 'act2', 'act3', 'act4', 'act5', 'act6', 'act7', 'rev_cuaderno', 'prueba1', 'prueba2'] as $field) {
                $rules["registros.{$matId}.{$field}"] = 'nullable|numeric|between:0,10';
            }
            $rules["registros.{$matId}.observaciones"] = 'nullable|string|max:500';
        }

        $this->validate($rules, $messages);

        DB::transaction(function () use ($asig) {
            foreach ($this->registros as $matId => $reg) {
                $record = RegistroNota::firstOrNew([
                    'matricula_id' => $matId,
                    'asignacion_docente_id' => $asig->id,
                    'trimestre' => $this->trimestre,
                ]);

                $record->fill([
                    'act1' => $reg['act1'] === '' ? null : $reg['act1'],
                    'act2' => $reg['act2'] === '' ? null : $reg['act2'],
                    'act3' => $reg['act3'] === '' ? null : $reg['act3'],
                    'act4' => $reg['act4'] === '' ? null : $reg['act4'],
                    'act5' => $reg['act5'] === '' ? null : $reg['act5'],
                    'act6' => $reg['act6'] === '' ? null : $reg['act6'],
                    'act7' => $reg['act7'] === '' ? null : $reg['act7'],
                    'rev_cuaderno' => $reg['rev_cuaderno'] === '' ? null : $reg['rev_cuaderno'],
                    'prueba1' => $reg['prueba1'] === '' ? null : $reg['prueba1'],
                    'prueba2' => $reg['prueba2'] === '' ? null : $reg['prueba2'],
                    'observaciones' => $reg['observaciones'] ?: null,
                ]);

                $record->save();
            }
        });

        $this->dispatch('notify', message: 'Calificaciones guardadas con éxito.', type: 'success');
        return redirect()->route('notas.index');
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.app')
            ->title('Registro de Notas | SIG');
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('notas.index') }}" class="text-xs text-indigo-600 hover:text-indigo-500 font-bold flex items-center gap-1">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                    Volver a Selección
                </a>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 mt-2">
                {{ $this->getAsignacion()->materia->nombre }} — {{ $this->getAsignacion()->seccion->nombre_completo }}
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Trimestre: <span class="font-bold text-slate-700">{{ $trimestre }}</span> | Docente: {{ $this->getAsignacion()->personal->nombre_completo }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button 
                type="button"
                wire:click="guardar"
                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:shadow transition duration-150"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Guardar Notas
            </button>
        </div>
    </div>

    <!-- Instructions / Weight info -->
    <div class="rounded-2xl border border-indigo-100 bg-indigo-50/35 p-4 text-xs text-indigo-800 flex items-start gap-2.5">
        <svg class="h-5 w-5 text-indigo-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 111.085 1.086L12.5 13.5H15m-6 0h1.5m-1.5-6h.008v.008H9V7.5zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div>
            <span class="font-bold">Fórmula de evaluación del MINED:</span>
            <ul class="list-disc pl-5 mt-1 space-y-1">
                <li><span class="font-semibold">Promedio de Actividades (35%):</span> Promedio de las actividades 1 a 7 y revisión de cuadernos que posean nota.</li>
                <li><span class="font-semibold">Prueba Objetiva 1 (30%):</span> Examen parcial.</li>
                <li><span class="font-semibold">Prueba Objetiva 2 (35%):</span> Examen trimestral.</li>
            </ul>
        </div>
    </div>

    <!-- Notes grid card -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[1200px]">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100 text-3xs font-extrabold text-slate-500 uppercase tracking-wider">
                        <th class="px-4 py-3.5 w-72">Estudiante</th>
                        @for($i = 1; $i <= 7; $i++)
                            <th class="px-2 py-3.5 text-center w-16">Act {{ $i }}</th>
                        @endfor
                        <th class="px-2 py-3.5 text-center w-20">Cuaderno</th>
                        <th class="px-2 py-3.5 text-center w-20">Prueba 1</th>
                        <th class="px-2 py-3.5 text-center w-20">Prueba 2</th>
                        <th class="px-2 py-3.5 text-center w-20 bg-indigo-50/50 text-indigo-700">Nota Final</th>
                        <th class="px-4 py-3.5">Observaciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($registros as $matId => $reg)
                        @php
                            $notaPrevia = $this->calcularNotaPrevia($matId);
                        @endphp
                        <tr class="hover:bg-slate-50/15 transition-colors">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-xs font-semibold text-slate-900">{{ $reg['nombre'] }}</div>
                                <div class="text-3xs text-slate-400">NIE: {{ $reg['nie'] }}</div>
                            </td>
                            
                            <!-- Actividades 1 a 7 -->
                            @for($i = 1; $i <= 7; $i++)
                                <td class="px-1 py-3 text-center">
                                    <input 
                                        type="number" 
                                        step="0.01" 
                                        min="0" 
                                        max="10"
                                        wire:model.live="registros.{{ $matId }}.act{{ $i }}"
                                        class="w-12 text-center text-xs rounded-lg border border-slate-300 py-1.5 focus:border-indigo-650 focus:outline-none focus:ring-1 focus:ring-indigo-650 text-slate-950 transition placeholder:text-slate-350"
                                    />
                                </td>
                            @endfor

                            <!-- Revisión de Cuaderno -->
                            <td class="px-1 py-3 text-center">
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    min="0" 
                                    max="10"
                                    wire:model.live="registros.{{ $matId }}.rev_cuaderno"
                                    class="w-14 text-center text-xs rounded-lg border border-slate-300 py-1.5 focus:border-indigo-650 focus:outline-none focus:ring-1 focus:ring-indigo-650 text-slate-950 transition"
                                />
                            </td>

                            <!-- Pruebas objetivas 1 y 2 -->
                            <td class="px-1 py-3 text-center">
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    min="0" 
                                    max="10"
                                    wire:model.live="registros.{{ $matId }}.prueba1"
                                    class="w-14 text-center text-xs rounded-lg border border-slate-300 py-1.5 focus:border-indigo-650 focus:outline-none focus:ring-1 focus:ring-indigo-650 text-slate-950 transition"
                                />
                            </td>
                            <td class="px-1 py-3 text-center">
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    min="0" 
                                    max="10"
                                    wire:model.live="registros.{{ $matId }}.prueba2"
                                    class="w-14 text-center text-xs rounded-lg border border-slate-300 py-1.5 focus:border-indigo-650 focus:outline-none focus:ring-1 focus:ring-indigo-650 text-slate-950 transition"
                                />
                            </td>

                            <!-- Promedio previsualizado -->
                            <td class="px-2 py-3 text-center bg-indigo-50/20 whitespace-nowrap">
                                @if($notaPrevia >= 5.00)
                                    <span class="inline-flex items-center rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 ring-1 ring-inset ring-emerald-600/10">
                                        {{ number_format($notaPrevia, 2) }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700 ring-1 ring-inset ring-rose-600/10">
                                        {{ number_format($notaPrevia, 2) }}
                                    </span>
                                @endif
                            </td>

                            <!-- Observaciones -->
                            <td class="px-4 py-3">
                                <input 
                                    type="text" 
                                    wire:model="registros.{{ $matId }}.observaciones"
                                    placeholder="Observación"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs focus:border-indigo-650 focus:outline-none focus:ring-1 focus:ring-indigo-650 text-slate-950 transition"
                                />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="px-4 py-8 text-center text-slate-400 text-xs italic">
                                No se encontraron estudiantes matriculados activos en esta sección.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Validation feedback summary -->
        @if($errors->any())
            <div class="p-6 border-t border-slate-100 bg-rose-50/20 text-rose-700 text-xs font-semibold space-y-1">
                <p>Existen errores en las calificaciones ingresadas. Por favor verifique:</p>
                <ul class="list-disc pl-5 mt-1">
                    <li>Las notas deben ser valores numéricos comprendidos entre 0.00 y 10.00.</li>
                </ul>
            </div>
        @endif
    </div>
</div>
