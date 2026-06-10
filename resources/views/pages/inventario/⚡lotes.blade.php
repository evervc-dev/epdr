<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Producto;
use App\Models\LoteAlimento;
use App\Models\MovimientoInventario;

new class extends Component
{
    use WithPagination;

    // Search and filters
    public string $search = '';
    public ?int $filtroProductoId = null;

    // Modal states
    public bool $modalAbierto = false;
    public bool $modalMovimientosAbierto = false;

    // Form fields
    public ?int $productoId = null;
    public string $codigoLote = '';
    public string $fechaIngreso = '';
    public int $cantidadAutorizada = 0;
    public int $unidadesCompletas = 0;
    public float $unidadesFraccionadas = 0.0;
    public float $pesoTotalKg = 0.0;
    public string $observaciones = '';

    // Batch movement detail tracking
    public ?int $verLoteId = null;
    public ?LoteAlimento $selectedLote = null;
    public $movimientos = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'filtroProductoId' => ['except' => null],
    ];

    public function mount()
    {
        abort_unless(auth()->user()->can('inventario.lotes'), 403);
        $this->fechaIngreso = now()->format('Y-m-d');
    }

    public function updatingSearch()
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

    public function getLotes()
    {
        return LoteAlimento::query()
            ->with('producto')
            ->when($this->search, function ($query) {
                $query->where(function($q) {
                    $q->where('codigo_lote', 'like', '%' . $this->search . '%')
                      ->orWhereHas('producto', function($qp) {
                          $qp->where('nombre', 'like', '%' . $this->search . '%');
                      });
                });
            })
            ->when($this->filtroProductoId, function ($query) {
                $query->where('producto_id', $this->filtroProductoId);
            })
            ->latest()
            ->paginate(10);
    }

    public function abrirModal()
    {
        abort_unless(auth()->user()->can('inventario.lotes'), 403);
        $this->resetErrorBag();
        $this->resetValidation();

        $this->productoId = null;
        $this->codigoLote = '';
        $this->fechaIngreso = now()->format('Y-m-d');
        $this->cantidadAutorizada = 0;
        $this->unidadesCompletas = 0;
        $this->unidadesFraccionadas = 0.0;
        $this->pesoTotalKg = 0.0;
        $this->observaciones = '';

        $this->modalAbierto = true;
    }

    public function updatedProductoId()
    {
        $this->recalcularPeso();
    }

    public function updatedUnidadesCompletas()
    {
        $this->recalcularPeso();
    }

    public function updatedUnidadesFraccionadas()
    {
        $this->recalcularPeso();
    }

    private function recalcularPeso()
    {
        if (!$this->productoId) {
            return;
        }

        $producto = Producto::find($this->productoId);
        if ($producto) {
            $unidadesTotal = $this->unidadesCompletas + $this->unidadesFraccionadas;
            $this->pesoTotalKg = round($unidadesTotal * $producto->peso_por_unidad, 3);
        }
    }

    public function guardar()
    {
        abort_unless(auth()->user()->can('inventario.lotes'), 403);

        $this->validate([
            'productoId' => 'required|exists:productos,id',
            'codigoLote' => 'required|string|max:50|unique:lotes_alimentos,codigo_lote',
            'fechaIngreso' => 'required|date',
            'cantidadAutorizada' => 'required|integer|min:1',
            'unidadesCompletas' => 'required|integer|min:0',
            'unidadesFraccionadas' => 'required|numeric|min:0',
            'pesoTotalKg' => 'required|numeric|min:0.01',
            'observaciones' => 'nullable|string',
        ]);

        LoteAlimento::create([
            'producto_id' => $this->productoId,
            'codigo_lote' => $this->codigoLote,
            'fecha_ingreso' => $this->fechaIngreso,
            'cantidad_autorizada' => $this->cantidadAutorizada,
            'unidades_completas' => $this->unidadesCompletas,
            'unidades_fraccionadas' => $this->unidadesFraccionadas,
            'peso_total_kg' => $this->pesoTotalKg,
            'observaciones' => $this->observaciones ?: null,
        ]);

        $this->modalAbierto = false;
        $this->dispatch('notify', message: 'Lote registrado con éxito.', type: 'success');
    }

    public function verMovimientos($loteId)
    {
        abort_unless(auth()->user()->can('inventario.lotes'), 403);
        $this->verLoteId = $loteId;
        $this->selectedLote = LoteAlimento::with('producto')->findOrFail($loteId);
        $this->movimientos = MovimientoInventario::with('registrador')
            ->where('lote_id', $loteId)
            ->orderBy('fecha', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $this->modalMovimientosAbierto = true;
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.app')
            ->title('Gestión de Lotes | SIG');
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Lotes de Alimentos</h1>
            <p class="mt-1 text-sm text-slate-500">Gestione los lotes de alimentos recibidos en la bodega escolar y su stock actual.</p>
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
                Registrar Lote
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs p-5 flex flex-col md:flex-row gap-4">
        <div class="flex-1 max-w-md">
            <label for="form_search" class="sr-only">Buscar por código de lote o producto</label>
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input 
                    id="form_search"
                    type="search" 
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar lote o producto..." 
                    class="block w-full rounded-xl border border-slate-350 bg-white py-2.5 pl-10 pr-3 text-slate-900 placeholder-slate-400 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition duration-150"
                />
            </div>
        </div>
        <div class="w-full md:w-64">
            <label for="form_filtro" class="sr-only">Filtrar por Producto</label>
            <select 
                id="form_filtro"
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
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Código Lote</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Producto</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Ingreso</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Peso Recibido</th>
                        <th scope="col" class="px-6 py-3.5 class text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Stock Actual</th>
                        <th scope="col" class="relative px-6 py-3.5 text-right">
                            <span class="sr-only">Acciones</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse($this->getLotes() as $l)
                        <tr class="hover:bg-slate-50/50 transition duration-150">
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-mono font-medium text-slate-900">{{ $l->codigo_lote }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-slate-900">
                                <div>{{ $l->producto->nombre }}</div>
                                <div class="text-xs text-slate-500">{{ $l->producto->codigo }}</div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                                {{ $l->fecha_ingreso->format('d/m/Y') }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                                {{ number_format($l->peso_total_kg, 2) }} {{ $l->producto->unidad_peso }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border {{ $l->stock_actual > 0 ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-slate-50 text-slate-600 border-slate-200' }}">
                                    {{ number_format($l->stock_actual, 2) }} {{ $l->producto->unidad_peso }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <button 
                                    type="button" 
                                    wire:click="verMovimientos({{ $l->id }})"
                                    class="text-indigo-600 hover:text-indigo-900 transition"
                                >
                                    Historial Movimientos
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-sm text-slate-500">
                                No se encontraron lotes de alimentos.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-200">
            <x-tabla-paginada :items="$this->getLotes()" />
        </div>
    </div>

    <!-- Modal Form -->
    @if($modalAbierto)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" aria-hidden="true" wire:click="$set('modalAbierto', false)"></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <div class="inline-block transform overflow-hidden rounded-3xl bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-xl sm:align-middle border border-slate-100">
                    <div class="bg-white px-6 pt-6 pb-4 sm:p-6">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
                            <h3 class="text-lg font-bold text-slate-900" id="modal-title">
                                Registrar Ingreso de Lote
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
                            <!-- Producto -->
                            <div>
                                <label for="form_producto" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Producto</label>
                                <select 
                                    id="form_producto"
                                    wire:model.live="productoId"
                                    class="block w-full rounded-xl border @error('productoId') border-rose-500 focus:ring-rose-500 focus:border-rose-500 @else border-slate-300 focus:ring-indigo-600 focus:border-indigo-600 @enderror bg-white px-3 py-2.5 text-slate-950 focus:outline-hidden sm:text-sm transition"
                                >
                                    <option value="">Seleccione un producto</option>
                                    @foreach($this->getProductos() as $prod)
                                        <option value="{{ $prod->id }}">{{ $prod->nombre }} ({{ $prod->peso_por_unidad }} {{ $prod->unidad_peso }})</option>
                                    @endforeach
                                </select>
                                @error('productoId')
                                    <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <!-- Código Lote -->
                                <div>
                                    <label for="form_lote" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Código del Lote</label>
                                    <input 
                                        id="form_lote"
                                        type="text" 
                                        wire:model="codigoLote"
                                        class="block w-full rounded-xl border @error('codigoLote') border-rose-500 focus:ring-rose-500 focus:border-rose-500 @else border-slate-300 focus:ring-indigo-600 focus:border-indigo-600 @enderror bg-white px-3 py-2.5 text-slate-950 focus:outline-hidden sm:text-sm transition"
                                        placeholder="Ej. LOT-1234"
                                    />
                                    @error('codigoLote')
                                        <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Fecha Ingreso -->
                                <div>
                                    <label for="form_fecha" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Fecha de Ingreso</label>
                                    <input 
                                        id="form_fecha"
                                        type="date" 
                                        wire:model="fechaIngreso"
                                        class="block w-full rounded-xl border @error('fechaIngreso') border-rose-500 focus:ring-rose-500 focus:border-rose-500 @else border-slate-300 focus:ring-indigo-600 focus:border-indigo-600 @enderror bg-white px-3 py-2.5 text-slate-950 focus:outline-hidden sm:text-sm transition"
                                    />
                                    @error('fechaIngreso')
                                        <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <!-- Cantidad Autorizada (según remisión) -->
                                <div class="col-span-3 sm:col-span-1">
                                    <label for="form_cant" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Cant. Autorizada</label>
                                    <input 
                                        id="form_cant"
                                        type="number" 
                                        wire:model="cantidadAutorizada"
                                        class="block w-full rounded-xl border @error('cantidadAutorizada') border-rose-500 focus:ring-rose-500 focus:border-rose-500 @else border-slate-300 focus:ring-indigo-600 focus:border-indigo-600 @enderror bg-white px-3 py-2.5 text-slate-950 focus:outline-hidden sm:text-sm transition"
                                    />
                                    @error('cantidadAutorizada')
                                        <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Unidades Completas (Físico) -->
                                <div class="col-span-3 sm:col-span-1">
                                    <label for="form_unid" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Unid. Completas</label>
                                    <input 
                                        id="form_unid"
                                        type="number" 
                                        wire:model.live="unidadesCompletas"
                                        class="block w-full rounded-xl border @error('unidadesCompletas') border-rose-500 focus:ring-rose-500 focus:border-rose-500 @else border-slate-300 focus:ring-indigo-600 focus:border-indigo-600 @enderror bg-white px-3 py-2.5 text-slate-950 focus:outline-hidden sm:text-sm transition"
                                    />
                                    @error('unidadesCompletas')
                                        <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Unidades Fraccionadas (Físico) -->
                                <div class="col-span-3 sm:col-span-1">
                                    <label for="form_frac" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Unid. Fraccionadas</label>
                                    <input 
                                        id="form_frac"
                                        type="number" 
                                        step="0.01"
                                        wire:model.live="unidadesFraccionadas"
                                        class="block w-full rounded-xl border @error('unidadesFraccionadas') border-rose-500 focus:ring-rose-500 focus:border-rose-500 @else border-slate-300 focus:ring-indigo-600 focus:border-indigo-600 @enderror bg-white px-3 py-2.5 text-slate-950 focus:outline-hidden sm:text-sm transition"
                                    />
                                    @error('unidadesFraccionadas')
                                        <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Peso total calculado -->
                            <div>
                                <label for="form_peso_tot" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Peso Total Calculado (Kg)</label>
                                <input 
                                    id="form_peso_tot"
                                    type="number" 
                                    step="0.001"
                                    wire:model="pesoTotalKg"
                                    class="block w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-slate-500 focus:outline-hidden sm:text-sm font-semibold"
                                    readonly
                                />
                                <p class="mt-1 text-2xs text-slate-400">Calculado automáticamente multiplicando las unidades por el peso unitario del producto.</p>
                            </div>

                            <!-- Observaciones -->
                            <div>
                                <label for="form_obs" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Observaciones</label>
                                <textarea 
                                    id="form_obs"
                                    wire:model="observaciones"
                                    rows="2"
                                    class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-950 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                                    placeholder="Detalles sobre el estado del lote..."
                                ></textarea>
                            </div>

                            <!-- Modal Actions -->
                            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5 mt-6">
                                <button 
                                    type="button" 
                                    wire:click="$set('modalAbierto', false)"
                                    class="rounded-xl border border-slate-300 bg-white hover:bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-700 transition"
                                >
                                    Cancelar
                                </button>
                                <button 
                                    type="submit"
                                    class="rounded-xl bg-indigo-600 hover:bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white shadow-xs transition"
                                >
                                    Guardar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Movements History -->
    @if($modalMovimientosAbierto)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" aria-hidden="true" wire:click="$set('modalMovimientosAbierto', false)"></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <div class="inline-block transform overflow-hidden rounded-3xl bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:align-middle border border-slate-100">
                    <div class="bg-white px-6 pt-6 pb-4 sm:p-6">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">
                                    Historial de Movimientos
                                </h3>
                                <p class="text-xs text-slate-500 mt-1">Lote: <span class="font-mono font-bold">{{ $selectedLote?->codigo_lote }}</span> | Producto: {{ $selectedLote?->producto->nombre }}</p>
                            </div>
                            <button 
                                type="button" 
                                wire:click="$set('modalMovimientosAbierto', false)"
                                class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Movement Timeline / Table -->
                        <div class="max-h-96 overflow-y-auto pr-2">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th scope="col" class="px-4 py-2.5 text-left text-xs font-bold text-slate-500 uppercase">Fecha</th>
                                        <th scope="col" class="px-4 py-2.5 text-left text-xs font-bold text-slate-500 uppercase">Tipo</th>
                                        <th scope="col" class="px-4 py-2.5 text-left text-xs font-bold text-slate-500 uppercase">Cantidad</th>
                                        <th scope="col" class="px-4 py-2.5 text-left text-xs font-bold text-slate-500 uppercase">Registrado Por</th>
                                        <th scope="col" class="px-4 py-2.5 text-left text-xs font-bold text-slate-500 uppercase">Detalle/Obs</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 bg-white">
                                    <!-- Base balance entry of the batch itself -->
                                    <tr class="bg-indigo-50/20">
                                        <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500">{{ $selectedLote?->fecha_ingreso->format('d/m/Y') }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-xs">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-2xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                Carga Inicial
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-xs font-bold text-slate-700">+{{ number_format($selectedLote?->peso_total_kg, 2) }} {{ $selectedLote?->producto->unidad_peso }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500">Sistema</td>
                                        <td class="px-4 py-3 text-xs text-slate-600 max-w-[200px] truncate">{{ $selectedLote?->observaciones ?: '-' }}</td>
                                    </tr>

                                    @forelse($movimientos as $m)
                                        <tr class="hover:bg-slate-50/50 transition duration-150">
                                            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500">{{ $m->fecha->format('d/m/Y') }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 text-xs">
                                                @if($m->tipo_movimiento === 'entrada')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-2xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                        Entrada
                                                    </span>
                                                @elseif($m->tipo_movimiento === 'salida')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-2xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                        Salida
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-2xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                        Merma
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-xs font-bold {{ $m->tipo_movimiento === 'entrada' ? 'text-emerald-600' : 'text-rose-600' }}">
                                                {{ $m->tipo_movimiento === 'entrada' ? '+' : '-' }}{{ number_format($m->cantidad, 2) }} {{ $m->unidad }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500">{{ $m->registrador?->name }}</td>
                                            <td class="px-4 py-3 text-xs text-slate-600 max-w-[200px] truncate" title="{{ $m->observaciones }}">
                                                @if($m->auditoria_id)
                                                    <span class="text-slate-400 font-semibold">(Ajuste de Auditoría #{{ $m->auditoria_id }})</span>
                                                @endif
                                                {{ $m->observaciones ?: '-' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <!-- No additional movements, only initial loading showing above -->
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Modal Actions -->
                        <div class="flex items-center justify-end border-t border-slate-100 pt-5 mt-6">
                            <button 
                                type="button" 
                                wire:click="$set('modalMovimientosAbierto', false)"
                                class="rounded-xl border border-slate-350 bg-white hover:bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-700 transition"
                            >
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
