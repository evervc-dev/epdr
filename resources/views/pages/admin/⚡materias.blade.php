<?php

use Livewire\Component;
use App\Models\Materia;
use App\Models\Grado;
use Illuminate\Validation\Rule;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    // Form fields
    public ?int $materiaId = null;
    public string $nombre = '';
    public string $codigo = '';
    public ?int $gradoId = null;

    // Filters
    public string $search = '';
    public ?int $filtroGradoId = null;

    // Toggle form mode
    public bool $isEditing = false;

    public function mount()
    {
        abort_unless(auth()->user()->can('materias.gestionar'), 403);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFiltroGradoId()
    {
        $this->resetPage();
    }

    public function getGrados()
    {
        return Grado::orderBy('orden')->get();
    }

    public function rules()
    {
        return [
            'nombre' => 'required|string|max:100',
            'codigo' => [
                'required',
                'string',
                'max:10',
                Rule::unique('materias', 'codigo')->ignore($this->materiaId),
            ],
            'gradoId' => 'required|exists:grados,id',
        ];
    }

    public function messages()
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no debe exceder 100 caracteres.',
            'codigo.required' => 'El código es obligatorio.',
            'codigo.max' => 'El código no debe exceder 10 caracteres.',
            'codigo.unique' => 'Este código ya está asignado a otra materia.',
            'gradoId.required' => 'Debe seleccionar un grado escolar.',
            'gradoId.exists' => 'El grado seleccionado es inválido.',
        ];
    }

    public function guardar()
    {
        abort_unless(auth()->user()->can('materias.gestionar'), 403);

        $this->validate();

        if ($this->isEditing && $this->materiaId) {
            $materia = Materia::findOrFail($this->materiaId);
            $materia->update([
                'nombre' => $this->nombre,
                'codigo' => strtoupper($this->codigo),
                'grado_id' => $this->gradoId,
            ]);
            $this->dispatch('notify', message: 'Materia actualizada con éxito.', type: 'success');
        } else {
            Materia::create([
                'nombre' => $this->nombre,
                'codigo' => strtoupper($this->codigo),
                'grado_id' => $this->gradoId,
            ]);
            $this->dispatch('notify', message: 'Materia registrada con éxito.', type: 'success');
        }

        $this->resetForm();
    }

    public function editar($id)
    {
        abort_unless(auth()->user()->can('materias.gestionar'), 403);

        $materia = Materia::findOrFail($id);
        $this->materiaId = $materia->id;
        $this->nombre = $materia->nombre;
        $this->codigo = $materia->codigo;
        $this->gradoId = $materia->grado_id;
        $this->isEditing = true;
    }

    public function eliminar($id)
    {
        abort_unless(auth()->user()->can('materias.gestionar'), 403);

        $materia = Materia::findOrFail($id);

        // Check if there are assignments
        if ($materia->asignaciones()->exists()) {
            $this->dispatch('notify', message: 'No se puede eliminar la materia porque tiene asignaciones docentes activas.', type: 'error');
            return;
        }

        $materia->delete();
        $this->dispatch('notify', message: 'Materia eliminada con éxito.', type: 'success');
        
        if ($this->materiaId === $id) {
            $this->resetForm();
        }
    }

    public function resetForm()
    {
        $this->materiaId = null;
        $this->nombre = '';
        $this->codigo = '';
        $this->gradoId = null;
        $this->isEditing = false;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function render()
    {
        $query = Materia::with('grado');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('materias.nombre', 'like', '%' . $this->search . '%')
                  ->orWhere('materias.codigo', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filtroGradoId) {
            $query->where('materias.grado_id', $this->filtroGradoId);
        }

        $materias = $query->join('grados', 'materias.grado_id', '=', 'grados.id')
            ->select('materias.*')
            ->orderBy('grados.orden')
            ->orderBy('materias.codigo')
            ->paginate(10);

        return $this->view(['materias' => $materias])
            ->layout('layouts.app')
            ->title('Gestión de Materias | SIG');
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Mantenimiento de Catálogos</span>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900 mt-1">Gestión de Materias</h1>
            <p class="mt-1 text-sm text-slate-500">Administre el catálogo nacional de asignaturas por grado escolar.</p>
        </div>
    </div>

    <!-- Main Grid layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left: Form Card -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs p-6 space-y-4 sticky top-24">
                <h2 class="text-lg font-bold text-slate-900 border-b border-slate-100 pb-3">
                    {{ $isEditing ? 'Editar Materia' : 'Nueva Materia' }}
                </h2>
                
                <form wire:submit.prevent="guardar" class="space-y-4">
                    <!-- Nombre -->
                    <div>
                        <label for="form_nombre" class="block text-sm font-semibold text-slate-700">Nombre de la Materia</label>
                        <input 
                            type="text" 
                            id="form_nombre"
                            wire:model="nombre"
                            placeholder="Ej: Lenguaje y Literatura"
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-slate-950 placeholder:text-slate-400 focus:border-indigo-650 focus:outline-none focus:ring-1 focus:ring-indigo-650 sm:text-sm transition"
                        />
                        <x-input-error field="nombre" />
                    </div>

                    <!-- Código -->
                    <div>
                        <label for="form_codigo" class="block text-sm font-semibold text-slate-700">Código</label>
                        <input 
                            type="text" 
                            id="form_codigo"
                            wire:model="codigo"
                            placeholder="Ej: LEN04"
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-slate-955 placeholder:text-slate-400 focus:border-indigo-655 focus:outline-none focus:ring-1 focus:ring-indigo-655 sm:text-sm transition uppercase"
                        />
                        <x-input-error field="codigo" />
                    </div>

                    <!-- Grado Escolar -->
                    <div>
                        <label for="form_grado" class="block text-sm font-semibold text-slate-700">Grado Escolar</label>
                        <select 
                            id="form_grado"
                            wire:model="gradoId"
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-slate-955 focus:border-indigo-655 focus:outline-none focus:ring-1 focus:ring-indigo-655 sm:text-sm transition"
                        >
                            <option value="">Seleccione un grado...</option>
                            @foreach($this->getGrados() as $grado)
                                <option value="{{ $grado->id }}">{{ $grado->nombre }}</option>
                            @endforeach
                        </select>
                        <x-input-error field="gradoId" />
                    </div>

                    <!-- Buttons -->
                    <div class="pt-2 flex gap-2">
                        <button 
                            type="submit"
                            class="flex-1 inline-flex justify-center rounded-xl bg-indigo-600 hover:bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:shadow transition duration-150"
                        >
                            {{ $isEditing ? 'Guardar Cambios' : 'Registrar Materia' }}
                        </button>
                        
                        @if($isEditing)
                            <button 
                                type="button"
                                wire:click="resetForm"
                                class="inline-flex justify-center rounded-xl bg-white hover:bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-700 border border-slate-300 transition duration-150"
                            >
                                Cancelar
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Right: Filter and List Card -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Filter box -->
            <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Search input -->
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.637 10.637z" />
                            </svg>
                        </div>
                        <input 
                            type="text"
                            wire:model.live="search"
                            placeholder="Buscar por nombre o código..."
                            class="block w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-10 pr-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 transition"
                        />
                    </div>

                    <!-- Grade filter dropdown -->
                    <div>
                        <select 
                            wire:model.live="filtroGradoId"
                            class="block w-full rounded-xl border border-slate-300 bg-white py-2.5 px-3 text-sm text-slate-900 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 transition"
                        >
                            <option value="">Todos los grados</option>
                            @foreach($this->getGrados() as $grado)
                                <option value="{{ $grado->id }}">{{ $grado->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- List table -->
            <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 border-b border-slate-100 text-xs font-bold text-slate-500 uppercase tracking-wider">
                                <th class="px-6 py-4 w-28">Código</th>
                                <th class="px-6 py-4">Materia / Asignatura</th>
                                <th class="px-6 py-4">Grado Escolar</th>
                                <th class="px-6 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($materias as $mat)
                                <tr class="hover:bg-slate-50/15 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900">
                                        {{ $mat->codigo }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-700">
                                        {{ $mat->nombre }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                        {{ $mat->grado->nombre }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                        <button 
                                            wire:click="editar({{ $mat->id }})"
                                            class="inline-flex items-center rounded-lg bg-indigo-50 hover:bg-indigo-100 p-2 text-indigo-700 transition"
                                            title="Editar Materia"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                                            </svg>
                                        </button>
                                        
                                        <button 
                                            wire:click="eliminar({{ $mat->id }})"
                                            wire:confirm="¿Estás seguro de que deseas eliminar esta materia? Esta acción no se puede deshacer."
                                            class="inline-flex items-center rounded-lg bg-rose-50 hover:bg-rose-100 p-2 text-rose-700 transition"
                                            title="Eliminar Materia"
                                        >
                                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-10 text-center text-slate-400 text-sm italic">
                                        No se encontraron materias registradas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-slate-100">
                    <x-tabla-paginada :items="$materias" />
                </div>
            </div>
        </div>
    </div>
</div>
