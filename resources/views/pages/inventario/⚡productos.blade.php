<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Producto;
use Illuminate\Validation\Rule;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $modalAbierto = false;

    // Form fields
    public ?int $productId = null;
    public string $codigo = '';
    public string $nombre = '';
    public string $tipo_embalaje = 'Sacos';
    public string $unidad_peso = 'kg';
    public float $peso_por_unidad = 1.0;
    public bool $activo = true;

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function mount()
    {
        abort_unless(auth()->user()->can('inventario.productos'), 403);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function getProductos()
    {
        return Producto::query()
            ->when($this->search, function ($query) {
                $query->where(function($q) {
                    $q->where('nombre', 'like', '%' . $this->search . '%')
                      ->orWhere('codigo', 'like', '%' . $this->search . '%');
                });
            })
            ->latest()
            ->paginate(10);
    }

    public function abrirModal($id = null)
    {
        abort_unless(auth()->user()->can('inventario.productos'), 403);
        $this->resetErrorBag();
        $this->resetValidation();

        if ($id) {
            $product = Producto::findOrFail($id);
            $this->productId = $product->id;
            $this->codigo = $product->codigo;
            $this->nombre = $product->nombre;
            $this->tipo_embalaje = $product->tipo_embalaje;
            $this->unidad_peso = $product->unidad_peso;
            $this->peso_por_unidad = $product->peso_por_unidad;
            $this->activo = $product->activo;
        } else {
            $this->productId = null;
            $this->codigo = '';
            $this->nombre = '';
            $this->tipo_embalaje = 'Sacos';
            $this->unidad_peso = 'kg';
            $this->peso_por_unidad = 1.0;
            $this->activo = true;
        }

        $this->modalAbierto = true;
    }

    public function guardar()
    {
        abort_unless(auth()->user()->can('inventario.productos'), 403);

        $rules = [
            'codigo' => [
                'required',
                'string',
                'max:20',
                Rule::unique('productos', 'codigo')->ignore($this->productId),
            ],
            'nombre' => 'required|string|max:255',
            'tipo_embalaje' => 'required|in:Sacos,Cajas,Latas,Otro',
            'unidad_peso' => 'required|string|max:10',
            'peso_por_unidad' => 'required|numeric|min:0.001',
            'activo' => 'required|boolean',
        ];

        $validated = $this->validate($rules);

        if ($this->productId) {
            $product = Producto::findOrFail($this->productId);
            $product->update($validated);
            $message = 'Producto actualizado con éxito.';
        } else {
            Producto::create($validated);
            $message = 'Producto creado con éxito.';
        }

        $this->modalAbierto = false;
        $this->dispatch('notify', message: $message, type: 'success');
    }

    public function toggleActivo($id)
    {
        abort_unless(auth()->user()->can('inventario.productos'), 403);
        $product = Producto::findOrFail($id);
        $product->activo = !$product->activo;
        $product->save();

        $this->dispatch('notify', message: 'Estado del producto actualizado.', type: 'success');
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.app')
            ->title('Productos Alimenticios | SIG');
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Productos de Alimentación</h1>
            <p class="mt-1 text-sm text-slate-500">Administre el catálogo de productos alimenticios de la bodega escolar.</p>
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
                Nuevo Producto
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs p-5">
        <div class="max-w-md">
            <label for="search_input" class="sr-only">Buscar Producto</label>
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input 
                    id="search_input"
                    type="search" 
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar por código o nombre..." 
                    class="block w-full rounded-xl border border-slate-350 bg-white py-2.5 pl-10 pr-3 text-slate-900 placeholder-slate-400 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition duration-150"
                />
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Código</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Nombre</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Embalaje</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Peso Unitario</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Estado</th>
                        <th scope="col" class="relative px-6 py-3.5 text-right">
                            <span class="sr-only">Acciones</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse($this->getProductos() as $p)
                        <tr class="hover:bg-slate-50/50 transition duration-150">
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-mono font-medium text-slate-900">{{ $p->codigo }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $p->nombre }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">{{ $p->tipo_embalaje }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                                {{ number_format($p->peso_por_unidad, 3) }} {{ $p->unidad_peso }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                <button 
                                    type="button"
                                    wire:click="toggleActivo({{ $p->id }})"
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium border cursor-pointer transition {{ $p->activo ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100' }}"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full {{ $p->activo ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                    {{ $p->activo ? 'Activo' : 'Inactivo' }}
                                </button>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <button 
                                    type="button" 
                                    wire:click="abrirModal({{ $p->id }})"
                                    class="text-indigo-600 hover:text-indigo-900 transition mr-4"
                                >
                                    Editar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-sm text-slate-500">
                                No se encontraron productos.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-200">
            <x-tabla-paginada :items="$this->getProductos()" />
        </div>
    </div>

    <!-- Modal Form -->
    @if($modalAbierto)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" aria-hidden="true" wire:click="$set('modalAbierto', false)"></div>

                <!-- Center elements -->
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <!-- Modal box -->
                <div class="relative inline-block transform overflow-hidden rounded-3xl bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle border border-slate-100">
                    <div class="bg-white px-6 pt-6 pb-4 sm:p-6">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
                            <h3 class="text-lg font-bold text-slate-900" id="modal-title">
                                {{ $productId ? 'Editar Producto' : 'Nuevo Producto' }}
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

                        <!-- Form fields -->
                        <form wire:submit.prevent="guardar" class="space-y-4">
                            <!-- Código -->
                            <div>
                                <label for="form_codigo" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Código de Producto</label>
                                <input 
                                    id="form_codigo"
                                    type="text" 
                                    wire:model="codigo"
                                    class="block w-full rounded-xl border @error('codigo') border-rose-500 focus:ring-rose-500 focus:border-rose-500 @else border-slate-300 focus:ring-indigo-600 focus:border-indigo-600 @enderror bg-white px-3 py-2.5 text-slate-950 focus:outline-hidden sm:text-sm transition duration-150"
                                    placeholder="Ej. ARR-01"
                                />
                                @error('codigo')
                                    <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Nombre -->
                            <div>
                                <label for="form_nombre" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Nombre del Producto</label>
                                <input 
                                    id="form_nombre"
                                    type="text" 
                                    wire:model="nombre"
                                    class="block w-full rounded-xl border @error('nombre') border-rose-500 focus:ring-rose-500 focus:border-rose-500 @else border-slate-300 focus:ring-indigo-600 focus:border-indigo-600 @enderror bg-white px-3 py-2.5 text-slate-950 focus:outline-hidden sm:text-sm transition duration-150"
                                    placeholder="Ej. Arroz Blanco Precocido"
                                />
                                @error('nombre')
                                    <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <!-- Embalaje -->
                                <div>
                                    <label for="form_embalaje" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Tipo de Embalaje</label>
                                    <select 
                                        id="form_embalaje"
                                        wire:model="tipo_embalaje"
                                        class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-950 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                                    >
                                        <option value="Sacos">Sacos</option>
                                        <option value="Cajas">Cajas</option>
                                        <option value="Latas">Latas</option>
                                        <option value="Otro">Otro</option>
                                    </select>
                                    @error('tipo_embalaje')
                                        <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Unidad Peso -->
                                <div>
                                    <label for="form_unidad" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Unidad de Peso</label>
                                    <input 
                                        id="form_unidad"
                                        type="text" 
                                        wire:model="unidad_peso"
                                        class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-950 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                                        placeholder="Ej. kg, lb"
                                    />
                                    @error('unidad_peso')
                                        <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Peso por Unidad -->
                            <div>
                                <label for="form_peso" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Peso Neto por Unidad</label>
                                <input 
                                    id="form_peso"
                                    type="number" 
                                    step="0.001"
                                    wire:model="peso_por_unidad"
                                    class="block w-full rounded-xl border @error('peso_por_unidad') border-rose-500 focus:ring-rose-500 focus:border-rose-500 @else border-slate-300 focus:ring-indigo-600 focus:border-indigo-600 @enderror bg-white px-3 py-2.5 text-slate-950 focus:outline-hidden sm:text-sm transition duration-150"
                                    placeholder="Ej. 22.5"
                                />
                                @error('peso_por_unidad')
                                    <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Activo -->
                            <div class="flex items-center gap-2 pt-2">
                                <input 
                                    id="form_activo"
                                    type="checkbox" 
                                    wire:model="activo"
                                    class="h-4 w-4 rounded-sm border-slate-300 text-indigo-600 focus:ring-indigo-500 transition cursor-pointer"
                                />
                                <label for="form_activo" class="text-sm font-semibold text-slate-700 cursor-pointer select-none">Habilitar producto en catálogo</label>
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
</div>
