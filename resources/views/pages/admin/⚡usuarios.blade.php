<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

new class extends Component
{
    use WithPagination;

    // Search and Pagination
    public string $search = '';
    
    // Modal states
    public bool $modalAbierto = false;
    public bool $mostrarConfirmacion = false;
    
    // Form fields
    public ?int $usuarioId = null;
    public string $nombre = '';
    public string $correo = '';
    public string $password = '';
    public string $rol = '';
    
    // Deletion tracking
    public ?int $eliminarId = null;

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function mount()
    {
        abort_unless(auth()->user()->can('usuarios.ver'), 403);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    // Helper methods for the template
    public function getUsuarios()
    {
        return User::query()
            ->when($this->search, function ($query) {
                $query->where(function($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->latest()
            ->paginate(10);
    }

    public function getRoles()
    {
        return Role::all();
    }

    public function abrirModal($id = null)
    {
        $this->resetErrorBag();
        $this->resetValidation();
        
        if ($id) {
            abort_unless(auth()->user()->can('usuarios.editar'), 403);
            
            $user = User::findOrFail($id);
            $this->usuarioId = $user->id;
            $this->nombre = $user->name;
            $this->correo = $user->email;
            $this->password = ''; // empty by default on edit
            $this->rol = $user->roles->first()?->name ?? '';
        } else {
            abort_unless(auth()->user()->can('usuarios.crear'), 403);
            
            $this->usuarioId = null;
            $this->nombre = '';
            $this->correo = '';
            $this->password = '';
            $this->rol = '';
        }

        $this->modalAbierto = true;
    }

    public function guardar()
    {
        if ($this->usuarioId) {
            abort_unless(auth()->user()->can('usuarios.editar'), 403);
        } else {
            abort_unless(auth()->user()->can('usuarios.crear'), 403);
        }

        $rules = [
            'nombre' => 'required|string|max:255',
            'correo' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->usuarioId),
            ],
            'rol' => 'required|exists:roles,name',
            'password' => $this->usuarioId ? 'nullable|string|min:4' : 'required|string|min:4',
        ];

        $messages = [
            'nombre.required' => 'El nombre es obligatorio.',
            'correo.required' => 'El correo electrónico es obligatorio.',
            'correo.email' => 'Debe ingresar un correo electrónico válido.',
            'correo.unique' => 'Este correo electrónico ya está registrado.',
            'rol.required' => 'Debe seleccionar un rol.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 4 caracteres.',
        ];

        $this->validate($rules, $messages);

        if ($this->usuarioId) {
            // Edit existing user
            $user = User::findOrFail($this->usuarioId);
            $user->name = $this->nombre;
            $user->email = $this->correo;
            
            if ($this->password) {
                $user->password = Hash::make($this->password);
            }
            
            $user->save();
            $user->syncRoles([$this->rol]);

            $this->dispatch('notify', message: 'Usuario actualizado con éxito.', type: 'success');
        } else {
            // Create new user
            $user = User::create([
                'name' => $this->nombre,
                'email' => $this->correo,
                'password' => Hash::make($this->password),
            ]);
            $user->assignRole($this->rol);

            $this->dispatch('notify', message: 'Usuario creado con éxito.', type: 'success');
        }

        $this->modalAbierto = false;
    }

    public function confirmarEliminar($id)
    {
        abort_unless(auth()->user()->can('usuarios.eliminar'), 403);
        
        $this->eliminarId = $id;
        $this->mostrarConfirmacion = true;
    }

    public function eliminar()
    {
        abort_unless(auth()->user()->can('usuarios.eliminar'), 403);

        if ($this->eliminarId === auth()->id()) {
            $this->dispatch('notify', message: 'No puedes eliminar tu propio usuario.', type: 'error');
            return;
        }

        $user = User::findOrFail($this->eliminarId);
        $user->delete();

        $this->dispatch('notify', message: 'Usuario eliminado con éxito.', type: 'success');
        $this->mostrarConfirmacion = false;
        $this->eliminarId = null;
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.app')
            ->title('Gestión de Usuarios | SIG');
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Gestión de Usuarios</h1>
            <p class="mt-1 text-sm text-slate-500">Mantenimiento y asignación de accesos al sistema.</p>
        </div>
        <div>
            @can('usuarios.crear')
            <button 
                wire:click="abrirModal()"
                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:shadow transition duration-150"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nuevo Usuario
            </button>
            @endcan
        </div>
    </div>

    <!-- Filters & Table Card -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs overflow-hidden">
        <!-- Search bar -->
        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
            <div class="max-w-md relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.637 10.637z" />
                    </svg>
                </div>
                <input 
                    type="text" 
                    wire:model.live="search"
                    placeholder="Buscar por nombre o correo..." 
                    class="block w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-955 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition duration-150"
                />
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Nombre</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Correo Electrónico</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Rol</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($this->getUsuarios() as $user)
                        <tr class="hover:bg-slate-50/30 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center font-bold">
                                        {{ $user->initials() }}
                                    </div>
                                    <span class="text-sm font-semibold text-slate-900">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                {{ $user->email }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($user->roles->isNotEmpty())
                                    <x-badge-rol :rol="$user->roles->first()->name" />
                                @else
                                    <span class="text-xs text-slate-400">Sin Rol</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    @can('usuarios.editar')
                                    <button 
                                        wire:click="abrirModal({{ $user->id }})"
                                        class="p-2 text-slate-500 hover:text-indigo-600 hover:bg-slate-50 rounded-lg transition"
                                        title="Editar"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                        </svg>
                                    </button>
                                    @endcan

                                    @can('usuarios.eliminar')
                                    @if($user->id !== auth()->id())
                                        <button 
                                            wire:click="confirmarEliminar({{ $user->id }})"
                                            class="p-2 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                                            title="Eliminar"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                        </button>
                                    @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center">
                                <div class="max-w-xs mx-auto text-slate-400">
                                    <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                    </svg>
                                    <p class="mt-2 text-sm font-semibold text-slate-700">No se encontraron usuarios</p>
                                    <p class="mt-1 text-xs">Intenta cambiar el término de búsqueda.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <x-tabla-paginada :items="$this->getUsuarios()" />
    </div>

    <!-- Form Modal (Create / Edit) -->
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
                    {{ $usuarioId ? 'Editar Usuario' : 'Nuevo Usuario' }}
                </h3>

                <form wire:submit.prevent="guardar" class="space-y-4">
                    <!-- Nombre -->
                    <div>
                        <label for="form_nombre" class="block text-sm font-semibold text-slate-700">Nombre Completo</label>
                        <input 
                            type="text" 
                            id="form_nombre"
                            wire:model="nombre"
                            placeholder="Nombre del usuario"
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-950 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                        />
                        <x-input-error field="nombre" />
                    </div>

                    <!-- Correo -->
                    <div>
                        <label for="form_correo" class="block text-sm font-semibold text-slate-700">Correo Electrónico</label>
                        <input 
                            type="email" 
                            id="form_correo"
                            wire:model="correo"
                            placeholder="ejemplo@cepja.edu.sv"
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-950 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                        />
                        <x-input-error field="correo" />
                    </div>

                    <!-- Rol Select -->
                    <div>
                        <label for="form_rol" class="block text-sm font-semibold text-slate-700">Rol de Acceso</label>
                        <select 
                            id="form_rol"
                            wire:model="rol"
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-950 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                        >
                            <option value="">Seleccione un rol...</option>
                            @foreach($this->getRoles() as $role)
                                <option value="{{ $role->name }}">{{ ucfirst($role->name) === 'Admin' ? 'Administrador' : ucfirst($role->name) }}</option>
                            @endforeach
                        </select>
                        <x-input-error field="rol" />
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="form_password" class="block text-sm font-semibold text-slate-700">
                            Contraseña {{ $usuarioId ? '(dejar en blanco para no cambiar)' : '' }}
                        </label>
                        <input 
                            type="password" 
                            id="form_password"
                            wire:model="password"
                            placeholder="{{ $usuarioId ? '••••••••' : 'Mínimo 4 caracteres' }}"
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-950 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                        />
                        <x-input-error field="password" />
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

    <!-- Delete Confirmation Modal -->
    <x-modal-confirm 
        wire:model="mostrarConfirmacion"
        title="Eliminar Usuario"
        message="¿Estás seguro de que deseas eliminar este usuario? Esta acción removerá su cuenta del sistema de forma permanente."
        confirmAction="eliminar"
        confirmText="Eliminar Usuario"
        type="danger"
    />
</div>
