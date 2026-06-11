<?php

use Livewire\Component;
use App\Models\LoteAlimento;
use App\Models\AuditoriaBodega;
use App\Models\DetalleAuditoriaBodega;
use App\Models\MovimientoInventario;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    // Active audit tracking
    public ?int $auditoriaActiveId = null;
    public ?AuditoriaBodega $activeAuditoria = null;
    public array $detallesTemp = []; // Holds physical stock values temporarily

    // New audit form
    public string $nuevaFecha = '';
    public string $nuevaObservaciones = '';

    // History and viewing details
    public ?int $verAuditoriaId = null;
    public ?AuditoriaBodega $selectedAuditoria = null;

    public function mount()
    {
        abort_unless(auth()->user()->can('inventario.auditorias'), 403);
        $this->nuevaFecha = now()->format('Y-m-d');
        $this->cargarAuditoriaActiva();
    }

    public function cargarAuditoriaActiva()
    {
        $active = AuditoriaBodega::where('estado', 'abierta')->first();
        if ($active) {
            $this->auditoriaActiveId = $active->id;
            $this->activeAuditoria = $active;
            
            // Populate temporary inputs for physical stock
            $this->detallesTemp = [];
            foreach ($active->detalles as $det) {
                $this->detallesTemp[$det->lote_id] = $det->stock_fisico;
            }
        } else {
            $this->auditoriaActiveId = null;
            $this->activeAuditoria = null;
            $this->detallesTemp = [];
        }
    }

    public function getAuditoriasCerradas()
    {
        return AuditoriaBodega::with('realizador')
            ->where('estado', 'cerrada')
            ->latest()
            ->get();
    }

    public function iniciarAuditoria()
    {
        abort_unless(auth()->user()->can('inventario.auditorias'), 403);

        $exists = AuditoriaBodega::where('estado', 'abierta')->exists();
        if ($exists) {
            $this->dispatch('notify', message: 'Ya existe una auditoría abierta en progreso.', type: 'error');
            return;
        }

        $this->validate([
            'nuevaFecha' => 'required|date',
            'nuevaObservaciones' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () {
            $auditoria = AuditoriaBodega::create([
                'fecha_auditoria' => $this->nuevaFecha,
                'realizada_por' => auth()->id(),
                'observaciones' => $this->nuevaObservaciones ?: null,
                'estado' => 'abierta',
            ]);

            // Load all batches
            $lotes = LoteAlimento::all();

            foreach ($lotes as $lote) {
                DetalleAuditoriaBodega::create([
                    'auditoria_id' => $auditoria->id,
                    'lote_id' => $lote->id,
                    'stock_sistema' => $lote->stock_actual,
                    'stock_fisico' => $lote->stock_actual, // Defaults to matching
                    'diferencia' => 0.0,
                ]);
            }
        });

        $this->nuevaObservaciones = '';
        $this->cargarAuditoriaActiva();
        $this->dispatch('notify', message: 'Auditoría iniciada con éxito.', type: 'success');
    }

    public function guardarDetalle($loteId, $stockFisicoValue)
    {
        abort_unless(auth()->user()->can('inventario.auditorias'), 403);

        if (!$this->auditoriaActiveId) {
            return;
        }

        $val = floatval($stockFisicoValue);
        if ($val < 0) {
            $this->dispatch('notify', message: 'El stock físico no puede ser menor a 0.', type: 'error');
            return;
        }

        $detalle = DetalleAuditoriaBodega::where('auditoria_id', $this->auditoriaActiveId)
            ->where('lote_id', $loteId)
            ->first();

        if ($detalle) {
            $detalle->stock_fisico = $val;
            $detalle->save();
        }

        $this->cargarAuditoriaActiva();
        $this->dispatch('notify', message: 'Stock guardado.', type: 'success');
    }

    public function cancelarAuditoria()
    {
        abort_unless(auth()->user()->can('inventario.auditorias'), 403);

        if (!$this->auditoriaActiveId) {
            return;
        }

        DB::transaction(function () {
            DetalleAuditoriaBodega::where('auditoria_id', $this->auditoriaActiveId)->delete();
            AuditoriaBodega::destroy($this->auditoriaActiveId);
        });

        $this->cargarAuditoriaActiva();
        $this->dispatch('notify', message: 'Auditoría cancelada y descartada.', type: 'info');
    }

    public function cerrarAuditoria()
    {
        abort_unless(auth()->user()->can('inventario.auditorias'), 403);

        if (!$this->auditoriaActiveId) {
            return;
        }

        DB::transaction(function () {
            $aud = AuditoriaBodega::with('detalles.lote.producto')->findOrFail($this->auditoriaActiveId);
            $aud->estado = 'cerrada';
            $aud->save();

            // Create movements to reconcile stock differences
            foreach ($aud->detalles as $det) {
                if ($det->diferencia != 0) {
                    $tipo = $det->diferencia > 0 ? 'entrada' : 'merma';
                    $absDiff = abs($det->diferencia);

                    MovimientoInventario::create([
                        'lote_id' => $det->lote_id,
                        'tipo_movimiento' => $tipo,
                        'cantidad' => $absDiff,
                        'unidad' => $det->lote->producto->unidad_peso,
                        'fecha' => now()->format('Y-m-d'),
                        'observaciones' => "Reconciliación de inventario según Auditoría #{$aud->id}",
                        'registrado_por' => auth()->id(),
                        'auditoria_id' => $aud->id,
                    ]);
                }
            }
        });

        $this->cargarAuditoriaActiva();
        $this->dispatch('notify', message: 'Auditoría cerrada y stock reconciliado.', type: 'success');
    }

    public function verDetalles($auditoriaId)
    {
        abort_unless(auth()->user()->can('inventario.auditorias'), 403);
        $this->verAuditoriaId = $auditoriaId;
        $this->selectedAuditoria = AuditoriaBodega::with(['realizador', 'detalles.lote.producto'])->findOrFail($auditoriaId);
    }

    public function cerrarVerDetalles()
    {
        $this->verAuditoriaId = null;
        $this->selectedAuditoria = null;
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.app')
            ->title('Auditoría de Bodega | SIG');
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Auditoría de Bodega</h1>
            <p class="mt-1 text-sm text-slate-500">Realice arqueos de stock físico en bodega, registre diferencias y ajuste el inventario.</p>
        </div>
    </div>

    @if($auditoriaActiveId && $activeAuditoria)
        <!-- ACTIVE AUDIT INTERFACE -->
        <div class="bg-indigo-900 rounded-3xl text-white p-6 shadow-md border border-indigo-950">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-2xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Auditoría en Curso (Abierta)
                    </span>
                    <h2 class="mt-3 text-xl font-bold">Iniciada el: {{ $activeAuditoria->fecha_auditoria->format('d/m/Y') }}</h2>
                    <p class="text-sm text-indigo-200 mt-1">Realizada por: {{ $activeAuditoria->realizador?->name }}</p>
                    @if($activeAuditoria->observaciones)
                        <p class="text-xs text-indigo-300 mt-2 italic">"{{ $activeAuditoria->observaciones }}"</p>
                    @endif
                </div>
                <div class="flex flex-wrap gap-3">
                    <button 
                        type="button"
                        wire:confirm="¿Está seguro de descartar la auditoría en progreso? Todos los datos ingresados se perderán."
                        wire:click="cancelarAuditoria"
                        class="rounded-xl border border-indigo-700 bg-indigo-950/40 hover:bg-indigo-950 px-4 py-2.5 text-sm font-semibold text-indigo-200 hover:text-white transition duration-150"
                    >
                        Descartar Auditoría
                    </button>
                    <button 
                        type="button"
                        wire:confirm="¿Está seguro de cerrar la auditoría escolar? El inventario en el sistema se reconciliará automáticamente según las diferencias detectadas."
                        wire:click="cerrarAuditoria"
                        class="rounded-xl bg-emerald-600 hover:bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-white shadow-xs transition duration-150"
                    >
                        Cerrar y Reconciliar Stock
                    </button>
                </div>
            </div>
        </div>

        <!-- ACTIVE AUDIT GRID -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs overflow-hidden">
            <div class="p-5 border-b border-slate-100 bg-slate-50/50">
                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Lotes a Auditar</h3>
                <p class="text-xs text-slate-500 mt-0.5">Introduzca el conteo de stock físico. Los cambios se guardan al hacer click fuera del cuadro.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase">Lote / Producto</th>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase">Stock Sistema</th>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase w-40">Stock Físico</th>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase">Diferencia</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach($activeAuditoria->detalles as $det)
                            <tr class="hover:bg-slate-50/50 transition duration-150">
                                <td class="px-6 py-4 text-sm">
                                    <div class="font-medium text-slate-900">{{ $det->lote->producto->nombre }}</div>
                                    <div class="text-xs font-mono text-slate-400">Lote: {{ $det->lote->codigo_lote }}</div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-700">
                                    {{ number_format($det->stock_sistema, 2) }} {{ $det->lote->producto->unidad_peso }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <div class="flex items-center gap-2">
                                        <input 
                                            type="number" 
                                            step="0.01"
                                            wire:blur="guardarDetalle({{ $det->lote_id }}, $event.target.value)"
                                            value="{{ $det->stock_fisico }}"
                                            class="block w-24 rounded-lg border border-slate-300 px-2.5 py-1.5 text-slate-900 focus:border-indigo-650 focus:outline-hidden focus:ring-1 focus:ring-indigo-650 sm:text-xs transition"
                                        />
                                        <span class="text-xs text-slate-500 font-semibold">{{ $det->lote->producto->unidad_peso }}</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    @if($det->diferencia == 0)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                            0.00 (Ok)
                                        </span>
                                    @elseif($det->diferencia > 0)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-100">
                                            +{{ number_format($det->diferencia, 2) }} (Excedente)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-100">
                                            {{ number_format($det->diferencia, 2) }} (Faltante)
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    @else
        <!-- NEW AUDIT CREATION -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- New Audit Form -->
            <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs p-6 lg:col-span-1 h-fit">
                <h2 class="text-lg font-bold text-slate-900 border-b border-slate-100 pb-3 mb-4">Iniciar Nueva Auditoría</h2>
                
                <form wire:submit.prevent="iniciarAuditoria" class="space-y-4">
                    <div>
                        <label for="audit_fecha" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Fecha de Auditoría</label>
                        <input 
                            id="audit_fecha"
                            type="date" 
                            wire:model="nuevaFecha"
                            class="block w-full rounded-xl border border-slate-350 bg-white px-3 py-2.5 text-slate-900 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                        />
                        @error('nuevaFecha')
                            <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="audit_obs" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Observaciones</label>
                        <textarea 
                            id="audit_obs"
                            wire:model="nuevaObservaciones"
                            rows="3"
                            class="block w-full rounded-xl border border-slate-350 bg-white px-3 py-2.5 text-slate-900 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                            placeholder="Ej. Arqueo físico mensual de bodega..."
                        ></textarea>
                        @error('nuevaObservaciones')
                            <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <button 
                        type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white shadow-xs transition"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.64 8.38a9 9 0 11-.13-4.41h5.83v10.4m0 0a3.3 3.3 0 013.3-3.3" />
                        </svg>
                        Iniciar Proceso
                    </button>
                </form>
            </div>

            <!-- Closed Audits History -->
            <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs overflow-hidden lg:col-span-2">
                <div class="p-5 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Historial de Auditorías Cerradas</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase">Fecha</th>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase">Auditor</th>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase">Detalle</th>
                                <th scope="col" class="relative px-6 py-3.5 text-right">
                                    <span class="sr-only">Acciones</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            @forelse($this->getAuditoriasCerradas() as $aud)
                                <tr class="hover:bg-slate-50/50 transition duration-150">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">
                                        {{ $aud->fecha_auditoria->format('d/m/Y') }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                                        {{ $aud->realizador?->name }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600 max-w-xs truncate" title="{{ $aud->observaciones }}">
                                        {{ $aud->observaciones ?: '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                        <button 
                                            type="button" 
                                            wire:click="verDetalles({{ $aud->id }})"
                                            class="text-indigo-600 hover:text-indigo-900 transition"
                                        >
                                            Ver Informe
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-10 text-center text-sm text-slate-500">
                                        No hay auditorías cerradas registradas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- View Closed Audit Report Modal -->
    @if($verAuditoriaId && $selectedAuditoria)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" aria-hidden="true" wire:click="cerrarVerDetalles"></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <div class="relative inline-block transform overflow-hidden rounded-3xl bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-3xl sm:align-middle border border-slate-100">
                    <div class="bg-white px-6 pt-6 pb-4 sm:p-6">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">
                                    Informe de Auditoría #{{ $selectedAuditoria->id }}
                                </h3>
                                <p class="text-xs text-slate-500 mt-1">Realizada el: {{ $selectedAuditoria->fecha_auditoria->format('d/m/Y') }} | Auditor: {{ $selectedAuditoria->realizador?->name }}</p>
                            </div>
                            <button 
                                type="button" 
                                wire:click="cerrarVerDetalles"
                                class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Observations -->
                        @if($selectedAuditoria->observaciones)
                            <div class="bg-slate-50 rounded-2xl p-4 mb-6 border border-slate-150">
                                <span class="block text-3xs font-extrabold uppercase text-slate-400 tracking-wider">Observaciones Generales</span>
                                <p class="text-sm text-slate-700 mt-1 italic">"{{ $selectedAuditoria->observaciones }}"</p>
                            </div>
                        @endif

                        <!-- Details list -->
                        <div class="max-h-96 overflow-y-auto pr-2">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th scope="col" class="px-4 py-2.5 text-left text-xs font-bold text-slate-500 uppercase">Lote / Producto</th>
                                        <th scope="col" class="px-4 py-2.5 text-left text-xs font-bold text-slate-500 uppercase">Stock Sistema</th>
                                        <th scope="col" class="px-4 py-2.5 text-left text-xs font-bold text-slate-500 uppercase">Stock Físico</th>
                                        <th scope="col" class="px-4 py-2.5 text-left text-xs font-bold text-slate-500 uppercase">Diferencia</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 bg-white">
                                    @foreach($selectedAuditoria->detalles as $det)
                                        <tr class="hover:bg-slate-50/50 transition duration-150">
                                            <td class="px-4 py-3 text-xs">
                                                <div class="font-medium text-slate-900">{{ $det->lote->producto->nombre }}</div>
                                                <div class="text-2xs font-mono text-slate-400">Lote: {{ $det->lote->codigo_lote }}</div>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500">
                                                {{ number_format($det->stock_sistema, 2) }} {{ $det->lote->producto->unidad_peso }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-700 font-medium">
                                                {{ number_format($det->stock_fisico, 2) }} {{ $det->lote->producto->unidad_peso }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-xs">
                                                @if($det->diferencia == 0)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-2xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                                        0.00
                                                    </span>
                                                @elseif($det->diferencia > 0)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-2xs font-semibold bg-blue-50 text-blue-700 border border-blue-100">
                                                        +{{ number_format($det->diferencia, 2) }}
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-2xs font-semibold bg-rose-50 text-rose-700 border border-rose-100">
                                                        {{ number_format($det->diferencia, 2) }}
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Modal Actions -->
                        <div class="flex items-center justify-end border-t border-slate-100 pt-5 mt-6">
                            <button 
                                type="button" 
                                wire:click="cerrarVerDetalles"
                                class="rounded-xl border border-slate-350 bg-white hover:bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-700 transition"
                            >
                                Cerrar Reporte
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
