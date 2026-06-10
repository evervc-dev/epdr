<?php

use Livewire\Component;
use App\Models\AnoLectivo;
use Illuminate\Validation\Rule;

new class extends Component
{
    // Form fields
    public ?int $anio = null;
    public ?string $fecha_inicio = null;
    public ?string $fecha_fin = null;

    // Modal state
    public bool $modalAbierto = false;

    public function mount()
    {
        abort_unless(auth()->user()->can('anio_lectivo.gestionar'), 403);
        $this->anio = now()->year;
    }

    public function getAnos()
    {
        return AnoLectivo::orderBy('anio', 'desc')->get();
    }

    public function getAnioActivo()
    {
        return AnoLectivo::where('activo', true)->first();
    }

    public function activar($id)
    {
        abort_unless(auth()->user()->can('anio_lectivo.gestionar'), 403);

        AnoLectivo::activar($id);

        $this->dispatch('notify', message: 'Año lectivo activado con éxito.', type: 'success');
    }

    public function abrirModal()
    {
        $this->resetErrorBag();
        $this->resetValidation();
        $this->anio = now()->year;
        $this->fecha_inicio = now()->year . '-01-15';
        $this->fecha_fin = now()->year . '-10-31';
        $this->modalAbierto = true;
    }

    public function guardar()
    {
        abort_unless(auth()->user()->can('anio_lectivo.gestionar'), 403);

        $rules = [
            'anio' => [
                'required',
                'integer',
                'min:2020',
                'max:2100',
                Rule::unique('anos_lectivos', 'anio'),
            ],
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
        ];

        $messages = [
            'anio.required' => 'El año es obligatorio.',
            'anio.integer' => 'Debe ingresar un año válido.',
            'anio.unique' => 'Este año lectivo ya está registrado.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'fecha_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
        ];

        $this->validate($rules, $messages);

        AnoLectivo::create([
            'anio' => $this->anio,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
            'activo' => false,
        ]);

        $this->dispatch('notify', message: 'Año lectivo creado con éxito.', type: 'success');
        $this->modalAbierto = false;
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.app')
            ->title('Gestión de Año Lectivo | SIG');
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Gestión de Años Lectivos</h1>
            <p class="mt-1 text-sm text-slate-500">Mantenimiento de los periodos escolares y control del año lectivo activo.</p>
        </div>
        <div>
            @can('anio_lectivo.gestionar')
            <button 
                wire:click="abrirModal"
                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:shadow transition duration-150"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nuevo Año Lectivo
            </button>
            @endcan
        </div>
    </div>

    <!-- Main Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Active Year Summary Card -->
        <div class="lg:col-span-1 space-y-6">
            <div class="relative overflow-hidden rounded-3xl bg-linear-to-br from-indigo-600 to-violet-800 text-white p-6 shadow-lg shadow-indigo-100">
                <!-- Decorative background elements -->
                <div class="absolute -right-8 -top-8 h-28 w-28 rounded-full bg-white/10 blur-xl"></div>
                <div class="absolute -left-12 -bottom-12 h-36 w-36 rounded-full bg-white/10 blur-xl"></div>

                <div class="relative space-y-6">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium bg-white/20 px-3 py-1 rounded-full uppercase tracking-wider text-xs">Periodo Activo</span>
                        <svg class="h-6 w-6 text-indigo-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                        </svg>
                    </div>

                    @if($this->getAnioActivo())
                        <div>
                            <h2 class="text-5xl font-black tracking-tight">{{ $this->getAnioActivo()->anio }}</h2>
                            <p class="text-indigo-200 mt-2 text-sm font-medium">Ciclo escolar en curso</p>
                        </div>

                        <div class="border-t border-white/20 pt-4 space-y-3">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-indigo-200">Fecha de Inicio:</span>
                                <span class="font-semibold">{{ $this->getAnioActivo()->fecha_inicio->format('d/m/Y') }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-indigo-200">Fecha de Cierre:</span>
                                <span class="font-semibold">{{ $this->getAnioActivo()->fecha_fin->format('d/m/Y') }}</span>
                            </div>
                        </div>
                    @else
                        <div class="py-6 text-center">
                            <p class="text-indigo-100 font-semibold text-lg">No hay ningún año lectivo activo</p>
                            <p class="text-indigo-200 text-xs mt-1">Activa uno de los años de la lista.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Years Table Card -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="font-bold text-slate-800">Historial de Años Lectivos</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 border-b border-slate-100">
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Año</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Fecha de Inicio</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Fecha de Fin</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($this->getAnos() as $ano)
                                <tr class="hover:bg-slate-50/30 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900">
                                        {{ $ano->anio }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                        {{ $ano->fecha_inicio->format('d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                        {{ $ano->fecha_fin->format('d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($ano->activo)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/10">
                                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                Activo
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-50 px-2 py-1 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-500/10">
                                                Inactivo
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        @if(!$ano->activo)
                                            <button 
                                                wire:click="activar({{ $ano->id }})"
                                                wire:confirm="¿Estás seguro de que deseas activar este año lectivo? Esto desactivará el periodo actual."
                                                class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-500 hover:bg-indigo-50 px-3 py-1.5 rounded-lg transition"
                                            >
                                                Activar
                                            </button>
                                        @else
                                            <span class="text-slate-400 text-xs italic">Periodo Activo</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center">
                                        <div class="max-w-xs mx-auto text-slate-400">
                                            <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                            </svg>
                                            <p class="mt-2 text-sm font-semibold text-slate-700">No se encontraron periodos</p>
                                            <p class="mt-1 text-xs">Registra un nuevo año lectivo para comenzar.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Modal (Create) -->
    @if($modalAbierto)
    <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" wire:click="$set('modalAbierto', false)"></div>

        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-3xl bg-white px-6 pb-6 pt-6 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg w-full">
                <!-- Close Button -->
                <button 
                    type="button" 
                    wire:click="$set('modalAbierto', false)"
                    class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 transition"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <h3 class="text-xl font-bold text-slate-900 mb-6">
                    Registrar Nuevo Año Lectivo
                </h3>

                <form wire:submit.prevent="guardar" class="space-y-4">
                    <!-- Año -->
                    <div>
                        <label for="form_anio" class="block text-sm font-semibold text-slate-700">Año Escolar</label>
                        <input 
                            type="number" 
                            id="form_anio"
                            wire:model="anio"
                            placeholder="Ej: {{ now()->year }}"
                            min="2020"
                            max="2100"
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-950 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                        />
                        <x-input-error field="anio" />
                    </div>

                    <!-- Fecha de Inicio -->
                    <div>
                        <label for="form_fecha_inicio" class="block text-sm font-semibold text-slate-700">Fecha de Inicio</label>
                        <input 
                            type="date" 
                            id="form_fecha_inicio"
                            wire:model="fecha_inicio"
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-950 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                        />
                        <x-input-error field="fecha_inicio" />
                    </div>

                    <!-- Fecha de Cierre -->
                    <div>
                        <label for="form_fecha_fin" class="block text-sm font-semibold text-slate-700">Fecha de Cierre</label>
                        <input 
                            type="date" 
                            id="form_fecha_fin"
                            wire:model="fecha_fin"
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-950 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                        />
                        <x-input-error field="fecha_fin" />
                    </div>

                    <div class="mt-6 flex flex-row-reverse gap-2">
                        <button 
                            type="submit"
                            class="inline-flex justify-center rounded-xl bg-indigo-600 hover:bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition duration-150"
                        >
                            Guardar
                        </button>
                        <button 
                            type="button" 
                            wire:click="$set('modalAbierto', false)"
                            class="inline-flex justify-center rounded-xl bg-white hover:bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-700 border border-slate-300 transition duration-150"
                        >
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
