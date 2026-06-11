<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Producto;
use App\Models\LoteAlimento;
use App\Models\MovimientoInventario;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    use WithPagination;

    // Filters
    public ?string $filtroTipo = null;
    public ?int $filtroProductoId = null;

    // Modal state
    public bool $modalAbierto = false;

    // Form fields
    public ?int $loteId = null;
    public string $tipoMovimiento = 'salida';
    public $cantidad = 0.0;
    public string $unidad = 'kg';
    public string $fecha = '';
    public string $observaciones = '';

    protected $queryString = [
        'filtroTipo' => ['except' => null],
        'filtroProductoId' => ['except' => null],
    ];

    public function mount()
    {
        abort_unless(auth()->user()->can('inventario.movimientos'), 403);
        $this->fecha = now()->format('Y-m-d');
    }

    public function updatingFiltroTipo()
    {
        $this->resetPage();
    }

    public function updatingFiltroProductoId()
    {
        $this->resetPage();
    }

    public function getProductos()
    {
        return Producto::where('activo', true)->orderBy('nombre')->get();
    }

    public function getLotesActivos()
    {
        // Get batches that have stock or products
        return LoteAlimento::with('producto')->latest()->get();
    }

    public function getMovimientos()
    {
        return MovimientoInventario::query()
            ->with(['lote.producto', 'registrador'])
            ->when($this->filtroTipo, function ($query) {
                $query->where('tipo_movimiento', $this->filtroTipo);
            })
            ->when($this->filtroProductoId, function ($query) {
                $query->whereHas('lote', function($q) {
                    $q->where('producto_id', $this->filtroProductoId);
                });
            })
            ->latest()
            ->paginate(10);
    }

    public function abrirModal()
    {
        abort_unless(auth()->user()->can('inventario.movimientos'), 403);
        $this->resetErrorBag();
        $this->resetValidation();

        $this->loteId = null;
        $this->tipoMovimiento = 'salida';
        $this->cantidad = 0.0;
        $this->unidad = 'kg';
        $this->fecha = now()->format('Y-m-d');
        $this->observaciones = '';

        $this->modalAbierto = true;
    }

    public function updatedLoteId()
    {
        if ($this->loteId) {
            $lote = LoteAlimento::with('producto')->find($this->loteId);
            if ($lote) {
                $this->unidad = $lote->producto->unidad_peso;
            }
        }
    }

    public function guardar()
    {
        abort_unless(auth()->user()->can('inventario.movimientos'), 403);

        $this->validate([
            'loteId' => 'required|exists:lotes_alimentos,id',
            'tipoMovimiento' => 'required|in:entrada,salida,merma',
            'cantidad' => 'required|numeric|min:0.01',
            'unidad' => 'required|string|max:10',
            'fecha' => 'required|date',
            'observaciones' => 'required|string|max:500',
        ]);

        $lote = LoteAlimento::findOrFail($this->loteId);

        // RN-07: Validar que no exceda el stock actual en salidas o mermas
        if (in_array($this->tipoMovimiento, ['salida', 'merma'])) {
            if ($this->cantidad > $lote->stock_actual) {
                $this->addError('cantidad', "La cantidad a retirar ({$this->cantidad} {$this->unidad}) excede el stock actual disponible ({$lote->stock_actual} {$this->unidad}) para este lote.");
                return;
            }
        }

        DB::transaction(function () use ($lote) {
            MovimientoInventario::create([
                'lote_id' => $this->loteId,
                'tipo_movimiento' => $this->tipoMovimiento,
                'cantidad' => $this->cantidad,
                'unidad' => $this->unidad,
                'fecha' => $this->fecha,
                'observaciones' => $this->observaciones,
                'registrado_por' => auth()->id(),
            ]);
        });

        $this->modalAbierto = false;
        $this->dispatch('notify', message: 'Movimiento registrado con éxito.', type: 'success');
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.app')
            ->title('Movimientos de Inventario | SIG');
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Movimientos de Inventario</h1>
            <p class="mt-1 text-sm text-slate-500">Registre entradas, salidas y mermas de alimentos, y mantenga el control de stock.</p>
        </div>
        <div>
            <button 
                type="button"
                wire:click="abrirModal()"
                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:shadow transition duration-150"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Registrar Movimiento
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs p-5 flex flex-col sm:flex-row gap-4">
        <!-- Tipo Movimiento Filter -->
        <div class="w-full sm:w-48">
            <label for="filter_tipo" class="sr-only">Tipo Movimiento</label>
            <select 
                id="filter_tipo"
                wire:model.live="filtroTipo"
                class="block w-full rounded-xl border border-slate-350 bg-white px-3 py-2.5 text-slate-900 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
            >
                <option value="">Todos los tipos</option>
                <option value="entrada">Entradas</option>
                <option value="salida">Salidas</option>
                <option value="merma">Mermas</option>
            </select>
        </div>
        <!-- Producto Filter -->
        <div class="w-full sm:w-64">
            <label for="filter_prod" class="sr-only">Filtrar por Producto</label>
            <select 
                id="filter_prod"
                wire:model.live="filtroProductoId"
                class="block w-full rounded-xl border border-slate-350 bg-white px-3 py-2.5 text-slate-900 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
            >
                <option value="">Todos los productos</option>
                @foreach($this->getProductos() as $prod)
                    <option value="{{ $prod->id }}">{{ $prod->nombre }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Fecha</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Producto / Lote</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Tipo</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Cantidad</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Registrado por</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Observaciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse($this->getMovimientos() as $mov)
                        <tr class="hover:bg-slate-50/50 transition duration-150">
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                                {{ $mov->fecha->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-slate-900">
                                <div>{{ $mov->lote->producto->nombre }}</div>
                                <div class="text-xs text-slate-400 font-mono">Lote: {{ $mov->lote->codigo_lote }}</div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                @if($mov->tipo_movimiento === 'entrada')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Entrada
                                    </span>
                                @elseif($mov->tipo_movimiento === 'salida')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        Salida
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        Merma
                                    </span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-bold {{ $mov->tipo_movimiento === 'entrada' ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $mov->tipo_movimiento === 'entrada' ? '+' : '-' }}{{ number_format($mov->cantidad, 2) }} {{ $mov->unidad }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                                {{ $mov->registrador?->name }}
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600 max-w-xs truncate" title="{{ $mov->observaciones }}">
                                @if($mov->auditoria_id)
                                    <span class="text-slate-400 font-semibold">(Ajuste de Auditoría)</span>
                                @endif
                                {{ $mov->observaciones }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-sm text-slate-500">
                                No se encontraron registros de movimientos de inventario.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-200">
            <x-tabla-paginada :items="$this->getMovimientos()" />
        </div>
    </div>

    <!-- Modal Form -->
    @if($modalAbierto)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" aria-hidden="true" wire:click="$set('modalAbierto', false)"></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <div class="relative inline-block transform overflow-hidden rounded-3xl bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle border border-slate-100">
                    <div class="bg-white px-6 pt-6 pb-4 sm:p-6">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
                            <h3 class="text-lg font-bold text-slate-900">
                                Registrar Movimiento de Inventario
                            </h3>
                            <button 
                                type="button" 
                                wire:click="$set('modalAbierto', false)"
                                class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <form wire:submit.prevent="guardar" class="space-y-4">
                            <!-- Lote -->
                            <div>
                                <label for="form_lote" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Lote de Alimento</label>
                                <select 
                                    id="form_lote"
                                    wire:model.live="loteId"
                                    class="block w-full rounded-xl border @error('loteId') border-rose-500 focus:ring-rose-500 focus:border-rose-500 @else border-slate-300 focus:ring-indigo-600 focus:border-indigo-600 @enderror bg-white px-3 py-2.5 text-slate-950 focus:outline-hidden sm:text-sm transition"
                                >
                                    <option value="">Seleccione un lote</option>
                                    @foreach($this->getLotesActivos() as $l)
                                        <option value="{{ $l->id }}">{{ $l->producto->nombre }} (Lote: {{ $l->codigo_lote }} - Stock: {{ $l->stock_actual }} {{ $l->producto->unidad_peso }})</option>
                                    @endforeach
                                </select>
                                @error('loteId')
                                    <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <!-- Tipo Movimiento -->
                                <div>
                                    <label for="form_tipo" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Tipo de Movimiento</label>
                                    <select 
                                        id="form_tipo"
                                        wire:model="tipoMovimiento"
                                        class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-950 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                                    >
                                        <option value="entrada">Entrada</option>
                                        <option value="salida">Salida</option>
                                        <option value="merma">Merma (Pérdida)</option>
                                    </select>
                                    @error('tipoMovimiento')
                                        <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Fecha -->
                                <div>
                                    <label for="form_fecha" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Fecha del Movimiento</label>
                                    <input 
                                        id="form_fecha"
                                        type="date" 
                                        wire:model="fecha"
                                        class="block w-full rounded-xl border @error('fecha') border-rose-500 focus:ring-rose-500 focus:border-rose-500 @else border-slate-300 focus:ring-indigo-600 focus:border-indigo-600 @enderror bg-white px-3 py-2.5 text-slate-950 focus:outline-hidden sm:text-sm transition"
                                    />
                                    @error('fecha')
                                        <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <!-- Cantidad -->
                                <div>
                                    <label for="form_cant" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Cantidad</label>
                                    <input 
                                        id="form_cant"
                                        type="number" 
                                        step="0.01"
                                        wire:model="cantidad"
                                        class="block w-full rounded-xl border @error('cantidad') border-rose-500 focus:ring-rose-500 focus:border-rose-500 @else border-slate-300 focus:ring-indigo-600 focus:border-indigo-600 @enderror bg-white px-3 py-2.5 text-slate-950 focus:outline-hidden sm:text-sm transition"
                                        placeholder="Ej. 15.5"
                                    />
                                    @error('cantidad')
                                        <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Unidad -->
                                <div>
                                    <label for="form_unidad" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Unidad de Peso</label>
                                    <input 
                                        id="form_unidad"
                                        type="text" 
                                        wire:model="unidad"
                                        class="block w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-slate-500 focus:outline-hidden sm:text-sm font-semibold"
                                        readonly
                                    />
                                </div>
                            </div>

                            <!-- Observaciones / Razón -->
                            <div>
                                <label for="form_obs" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Razón u Observaciones</label>
                                <textarea 
                                    id="form_obs"
                                    wire:model="observaciones"
                                    rows="3"
                                    class="block w-full rounded-xl border @error('observaciones') border-rose-500 focus:ring-rose-500 focus:border-rose-500 @else border-slate-300 focus:ring-indigo-600 focus:border-indigo-600 @enderror bg-white px-3 py-2.5 text-slate-950 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                                    placeholder="Ej. Consumo de alimentos diario para almuerzo escolar."
                                ></textarea>
                                @error('observaciones')
                                    <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Modal Actions -->
                            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5 mt-6">
                                <button 
                                    type="button" 
                                    wire:click="$set('modalAbierto', false)"
                                    class="rounded-xl border border-slate-350 bg-white hover:bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-700 transition"
                                >
                                    Cancelar
                                </button>
                                <button 
                                    type="submit"
                                    class="rounded-xl bg-indigo-600 hover:bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white shadow-xs transition"
                                >
                                    Registrar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
