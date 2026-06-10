<?php

use Livewire\Component;
use App\Models\Grado;
use App\Models\Seccion;
use App\Models\AnoLectivo;
use Illuminate\Validation\Rule;

new class extends Component
{
    public ?int $gradoSeleccionadoId = null;
    public string $letra = '';
    public string $turno = 'Mañana';

    public function mount()
    {
        abort_unless(auth()->user()->can('grados.gestionar'), 403);
        
        $firstGrado = Grado::orderBy('orden')->first();
        if ($firstGrado) {
            $this->gradoSeleccionadoId = $firstGrado->id;
        }
    }

    public function getGrados()
    {
        return Grado::orderBy('orden')->get();
    }

    public function getSelectedGrado()
    {
        return Grado::find($this->gradoSeleccionadoId);
    }

    public function getAnioActivo()
    {
        return AnoLectivo::where('activo', true)->first();
    }

    public function getSecciones()
    {
        $anoLectivo = $this->getAnioActivo();
        if (!$anoLectivo || !$this->gradoSeleccionadoId) {
            return collect();
        }

        return Seccion::where('grado_id', $this->gradoSeleccionadoId)
            ->where('ano_lectivo_id', $anoLectivo->id)
            ->orderBy('letra')
            ->get();
    }

    public function seleccionarGrado($id)
    {
        $this->gradoSeleccionadoId = $id;
        $this->letra = '';
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function agregarSeccion()
    {
        abort_unless(auth()->user()->can('grados.gestionar'), 403);

        $anoLectivo = $this->getAnioActivo();
        if (!$anoLectivo) {
            $this->dispatch('notify', message: 'No hay un año lectivo activo. Debe activar uno primero.', type: 'error');
            return;
        }

        $rules = [
            'letra' => [
                'required',
                'string',
                'max:1',
                Rule::unique('secciones', 'letra')
                    ->where('grado_id', $this->gradoSeleccionadoId)
                    ->where('ano_lectivo_id', $anoLectivo->id),
            ],
            'turno' => 'required|string|max:50',
        ];

        $messages = [
            'letra.required' => 'La letra es obligatoria.',
            'letra.max' => 'Debe ingresar solo un carácter (ej: A).',
            'letra.unique' => 'Esta sección ya existe en este grado para el año actual.',
            'turno.required' => 'El turno es obligatorio.',
        ];

        $this->validate($rules, $messages);

        Seccion::create([
            'grado_id' => $this->gradoSeleccionadoId,
            'ano_lectivo_id' => $anoLectivo->id,
            'letra' => strtoupper($this->letra),
            'turno' => $this->turno,
        ]);

        $this->letra = '';
        $this->dispatch('notify', message: 'Sección agregada con éxito.', type: 'success');
    }

    public function eliminarSeccion($id)
    {
        abort_unless(auth()->user()->can('grados.gestionar'), 403);

        $seccion = Seccion::findOrFail($id);

        if ($seccion->matriculas()->exists()) {
            $this->dispatch('notify', message: 'No se puede eliminar la sección porque tiene estudiantes matriculados.', type: 'error');
            return;
        }

        if ($seccion->asignacionesDocentes()->exists()) {
            $this->dispatch('notify', message: 'No se puede eliminar la sección porque tiene docentes asignados.', type: 'error');
            return;
        }

        $seccion->delete();

        $this->dispatch('notify', message: 'Sección eliminada con éxito.', type: 'success');
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.app')
            ->title('Grados y Secciones | SIG');
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Grados y Secciones</h1>
            <p class="mt-1 text-sm text-slate-500">Administración de la oferta educativa escolar y distribución de secciones.</p>
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
            <p class="mt-2 text-sm text-slate-500">Para gestionar los grados y secciones del ciclo escolar actual, primero debe registrar y activar un año lectivo.</p>
            <div class="mt-6">
                <a 
                    href="{{ route('admin.ano-lectivo') }}"
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white shadow-xs transition"
                >
                    Ir a Año Lectivo
                </a>
            </div>
        </div>
    @else
        <!-- Dual Pane Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            
            <!-- Left Pane: Grades List -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs overflow-hidden">
                    <div class="p-5 border-b border-slate-100 bg-slate-50/50">
                        <h2 class="font-bold text-slate-800 text-sm uppercase tracking-wider">Grados Académicos</h2>
                    </div>
                    <div class="divide-y divide-slate-100 max-h-[600px] overflow-y-auto">
                        @foreach($this->getGrados() as $grado)
                            <button
                                wire:click="seleccionarGrado({{ $grado->id }})"
                                class="w-full text-left px-5 py-4 flex items-center justify-between text-sm transition-all focus:outline-hidden {{ $gradoSeleccionadoId === $grado->id ? 'bg-indigo-50 text-indigo-700 font-bold border-l-4 border-indigo-600' : 'text-slate-700 hover:bg-slate-50/70 border-l-4 border-transparent' }}"
                            >
                                <span>{{ $grado->nombre }}</span>
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">
                                    {{ ucfirst($grado->nivel) === 'Basica' ? 'Básica' : 'Parvularia' }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Right Pane: Sections details -->
            <div class="lg:col-span-3 space-y-6">
                @if($this->getSelectedGrado())
                    <!-- Selected Grade Info & Add Section Form -->
                    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs p-6 space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-100 pb-5">
                            <div>
                                <h2 class="text-xl font-bold text-slate-900">{{ $this->getSelectedGrado()->nombre }}</h2>
                                <p class="text-xs text-slate-400 mt-1">Configuración de secciones para el año actual.</p>
                            </div>

                            @can('grados.gestionar')
                            <!-- Inline creation form -->
                            <form wire:submit.prevent="agregarSeccion" class="flex flex-wrap items-center gap-3">
                                <div>
                                    <input 
                                        type="text" 
                                        wire:model="letra"
                                        placeholder="Letra (Ej: A)"
                                        maxlength="1"
                                        class="block w-28 rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-950 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 text-sm transition"
                                    />
                                </div>
                                <div>
                                    <select 
                                        wire:model="turno"
                                        class="block w-32 rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-950 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 text-sm transition"
                                    >
                                        <option value="Mañana">Mañana</option>
                                        <option value="Tarde">Tarde</option>
                                    </select>
                                </div>
                                <button 
                                    type="submit"
                                    class="inline-flex items-center justify-center rounded-xl bg-indigo-600 hover:bg-indigo-500 px-4 py-2 text-sm font-semibold text-white shadow-xs transition"
                                >
                                    Agregar
                                </button>
                            </form>
                            @endcan
                        </div>
                        
                        <!-- Validation feedback -->
                        <div class="mt-1">
                            <x-input-error field="letra" />
                            <x-input-error field="turno" />
                        </div>

                        <!-- Sections list table -->
                        <div class="border border-slate-150 rounded-2xl overflow-hidden">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50/50 border-b border-slate-100">
                                        <th class="px-5 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Sección</th>
                                        <th class="px-5 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Turno</th>
                                        <th class="px-5 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Matrículas</th>
                                        <th class="px-5 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse($this->getSecciones() as $seccion)
                                        <tr class="hover:bg-slate-50/20 transition-colors">
                                            <td class="px-5 py-3.5 whitespace-nowrap text-sm font-bold text-slate-900">
                                                Sección "{{ $seccion->letra }}"
                                            </td>
                                            <td class="px-5 py-3.5 whitespace-nowrap text-sm text-slate-600">
                                                {{ $seccion->turno }}
                                            </td>
                                            <td class="px-5 py-3.5 whitespace-nowrap">
                                                <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-600/10">
                                                    {{ $seccion->matriculas()->count() }} alumnos
                                                </span>
                                            </td>
                                            <td class="px-5 py-3.5 whitespace-nowrap text-right text-sm font-medium">
                                                @can('grados.gestionar')
                                                <button 
                                                    wire:click="eliminarSeccion({{ $seccion->id }})"
                                                    wire:confirm="¿Estás seguro de que deseas eliminar la Sección {{ $seccion->letra }}? Esta acción no se puede deshacer."
                                                    class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                                                    title="Eliminar Sección"
                                                >
                                                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                    </svg>
                                                </button>
                                                @else
                                                -
                                                @endcan
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-5 py-8 text-center text-slate-400 text-xs italic">
                                                No hay secciones configuradas para este grado en el año lectivo activo.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
