<?php

use Livewire\Component;
use App\Models\Personal;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public ?int $personalId = null;

    // Personal fields
    public string $dui = '';
    public string $nombres = '';
    public string $apellidos = '';
    public ?string $fecha_nacimiento = null;
    public string $genero = 'M';
    public string $telefono = '';
    public string $correo = '';
    public string $tipo = 'docente';
    public string $especialidad = '';
    public ?string $fecha_ingreso = null;
    public bool $activo = true;
    public ?int $user_id = null;

    // User account creation options
    public string $user_option = 'none'; // 'none', 'existing', 'new'
    public ?int $selected_existing_user_id = null;
    
    // New user fields
    public string $new_user_password = '';
    public string $new_user_role = '';

    public function mount($id = null)
    {
        if ($id) {
            abort_unless(auth()->user()->can('personal.editar'), 403);
            
            $persona = Personal::findOrFail($id);
            $this->personalId = $persona->id;
            $this->dui = $persona->dui;
            $this->nombres = $persona->nombres;
            $this->apellidos = $persona->apellidos;
            $this->fecha_nacimiento = $persona->fecha_nacimiento->format('Y-m-d');
            $this->genero = $persona->genero;
            $this->telefono = $persona->telefono ?? '';
            $this->correo = $persona->correo ?? '';
            $this->tipo = $persona->tipo;
            $this->especialidad = $persona->especialidad ?? '';
            $this->fecha_ingreso = $persona->fecha_ingreso->format('Y-m-d');
            $this->activo = $persona->activo;
            $this->user_id = $persona->user_id;

            if ($this->user_id) {
                $this->user_option = 'existing';
                $this->selected_existing_user_id = $this->user_id;
            }
        } else {
            abort_unless(auth()->user()->can('personal.crear'), 403);
            $this->fecha_ingreso = now()->format('Y-m-d');
        }
    }

    public function getUnlinkedUsers()
    {
        return User::query()
            ->whereDoesntHave('personal', function ($q) {
                $q->when($this->personalId, fn($sub) => $sub->where('id', '!=', $this->personalId));
            })
            ->orderBy('name')
            ->get();
    }

    public function getRoles()
    {
        return Role::all();
    }

    public function guardar()
    {
        if ($this->personalId) {
            abort_unless(auth()->user()->can('personal.editar'), 403);
        } else {
            abort_unless(auth()->user()->can('personal.crear'), 403);
        }

        $rules = [
            'dui' => [
                'required',
                'string',
                'regex:/^\d{8}-\d$/', // Salvadoran DUI format: 00000000-0
                Rule::unique('personal', 'dui')->ignore($this->personalId),
            ],
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'fecha_nacimiento' => 'required|date|before:-18 years',
            'genero' => 'required|in:M,F',
            'tipo' => 'required|in:docente,administrativo,servicio',
            'fecha_ingreso' => 'required|date',
            'correo' => [
                'nullable',
                'email',
                Rule::unique('personal', 'correo')->ignore($this->personalId),
            ],
            'telefono' => 'nullable|string|max:20',
            'especialidad' => 'nullable|string|max:100',
        ];

        $messages = [
            'dui.required' => 'El DUI es obligatorio.',
            'dui.regex' => 'El formato del DUI debe ser 00000000-0 (incluyendo el guión).',
            'dui.unique' => 'Este DUI ya está registrado.',
            'nombres.required' => 'Los nombres son obligatorios.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            'fecha_nacimiento.before' => 'El empleado debe ser mayor de 18 años.',
            'tipo.required' => 'El tipo de personal es obligatorio.',
            'fecha_ingreso.required' => 'La fecha de ingreso es obligatoria.',
            'correo.email' => 'Debe ingresar un correo válido.',
            'correo.unique' => 'Este correo ya está registrado en el personal.',
        ];

        if ($this->user_option === 'new') {
            $rules['correo'][] = 'required';
            $rules['new_user_password'] = 'required|string|min:4';
            $rules['new_user_role'] = 'required|exists:roles,name';
            
            $messages['correo.required'] = 'El correo electrónico es obligatorio para crear una cuenta de usuario.';
            $messages['new_user_password.required'] = 'La contraseña es obligatoria para la nueva cuenta.';
            $messages['new_user_role.required'] = 'Debe seleccionar un rol.';
        }

        $this->validate($rules, $messages);

        if ($this->user_option === 'new') {
            $emailExists = User::where('email', $this->correo)->exists();
            if ($emailExists) {
                $this->addError('correo', 'Este correo electrónico ya está registrado como cuenta de usuario.');
                return;
            }
        }

        DB::transaction(function () {
            $resolvedUserId = null;

            if ($this->user_option === 'existing') {
                $resolvedUserId = $this->selected_existing_user_id ?: null;
            } elseif ($this->user_option === 'new') {
                $newUser = User::create([
                    'name' => $this->nombres . ' ' . $this->apellidos,
                    'email' => $this->correo,
                    'password' => Hash::make($this->new_user_password),
                ]);
                $newUser->assignRole($this->new_user_role);
                $resolvedUserId = $newUser->id;
            }

            if ($this->personalId) {
                $persona = Personal::findOrFail($this->personalId);
                $persona->update([
                    'dui' => $this->dui,
                    'nombres' => $this->nombres,
                    'apellidos' => $this->apellidos,
                    'fecha_nacimiento' => $this->fecha_nacimiento,
                    'genero' => $this->genero,
                    'telefono' => $this->telefono ?: null,
                    'correo' => $this->correo ?: null,
                    'tipo' => $this->tipo,
                    'especialidad' => $this->tipo === 'docente' ? $this->especialidad : null,
                    'fecha_ingreso' => $this->fecha_ingreso,
                    'activo' => $this->activo,
                    'user_id' => $resolvedUserId,
                ]);
            } else {
                Personal::create([
                    'dui' => $this->dui,
                    'nombres' => $this->nombres,
                    'apellidos' => $this->apellidos,
                    'fecha_nacimiento' => $this->fecha_nacimiento,
                    'genero' => $this->genero,
                    'telefono' => $this->telefono ?: null,
                    'correo' => $this->correo ?: null,
                    'tipo' => $this->tipo,
                    'especialidad' => $this->tipo === 'docente' ? $this->especialidad : null,
                    'fecha_ingreso' => $this->fecha_ingreso,
                    'activo' => $this->activo,
                    'user_id' => $resolvedUserId,
                ]);
            }
        });

        $this->dispatch('notify', message: $this->personalId ? 'Expediente de personal actualizado.' : 'Personal registrado con éxito.', type: 'success');
        return redirect()->route('personal.index');
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.app')
            ->title(($this->personalId ? 'Editar Personal' : 'Nuevo Personal') . ' | SIG');
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">
                {{ $personalId ? 'Editar Personal' : 'Nuevo Personal' }}
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ $personalId ? 'Modifique los campos y configure la cuenta de acceso al sistema del empleado.' : 'Complete el expediente del nuevo recurso humano.' }}
            </p>
        </div>
        <div>
            <a 
                href="{{ route('personal.index') }}"
                class="inline-flex items-center gap-2 rounded-xl bg-white hover:bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-700 border border-slate-300 shadow-2xs transition"
            >
                Cancelar y Volver
            </a>
        </div>
    </div>

    <form wire:submit.prevent="guardar" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Personal info column (2/3 width on large screens) -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs p-6 space-y-6">
                <h2 class="text-lg font-bold text-slate-900 border-b border-slate-100 pb-3">Información General</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- DUI -->
                    <div>
                        <label for="dui" class="block text-sm font-semibold text-slate-700">DUI (Documento Único de Identidad)</label>
                        <input 
                            type="text" 
                            id="dui"
                            wire:model="dui"
                            placeholder="Ej: 00000000-0"
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-950 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                        />
                        <x-input-error field="dui" />
                    </div>

                    <!-- Tipo -->
                    <div>
                        <label for="tipo" class="block text-sm font-semibold text-slate-700">Rol / Tipo de Personal</label>
                        <select 
                            id="tipo"
                            wire:model.live="tipo"
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-950 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                        >
                            <option value="docente">Docente / Profesor</option>
                            <option value="administrativo">Administrativo / Dirección</option>
                            <option value="servicio">Servicios Generales</option>
                        </select>
                        <x-input-error field="tipo" />
                    </div>

                    <!-- Nombres -->
                    <div>
                        <label for="nombres" class="block text-sm font-semibold text-slate-700">Nombres</label>
                        <input 
                            type="text" 
                            id="nombres"
                            wire:model="nombres"
                            placeholder="Nombres del empleado"
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-950 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                        />
                        <x-input-error field="nombres" />
                    </div>

                    <!-- Apellidos -->
                    <div>
                        <label for="apellidos" class="block text-sm font-semibold text-slate-700">Apellidos</label>
                        <input 
                            type="text" 
                            id="apellidos"
                            wire:model="apellidos"
                            placeholder="Apellidos del empleado"
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-950 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                        />
                        <x-input-error field="apellidos" />
                    </div>

                    <!-- Fecha de Nacimiento -->
                    <div>
                        <label for="fecha_nacimiento" class="block text-sm font-semibold text-slate-700">Fecha de Nacimiento</label>
                        <input 
                            type="date" 
                            id="fecha_nacimiento"
                            wire:model="fecha_nacimiento"
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-950 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                        />
                        <x-input-error field="fecha_nacimiento" />
                    </div>

                    <!-- Género -->
                    <div>
                        <label for="genero" class="block text-sm font-semibold text-slate-700">Género</label>
                        <select 
                            id="genero"
                            wire:model="genero"
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-950 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                        >
                            <option value="M">Masculino</option>
                            <option value="F">Femenino</option>
                        </select>
                        <x-input-error field="genero" />
                    </div>

                    <!-- Teléfono -->
                    <div>
                        <label for="telefono" class="block text-sm font-semibold text-slate-700">Teléfono Móvil / Fijo</label>
                        <input 
                            type="text" 
                            id="telefono"
                            wire:model="telefono"
                            placeholder="Ej: 2222-2222"
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-950 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                        />
                        <x-input-error field="telefono" />
                    </div>

                    <!-- Correo Electrónico -->
                    <div>
                        <label for="correo" class="block text-sm font-semibold text-slate-700">Correo Electrónico</label>
                        <input 
                            type="email" 
                            id="correo"
                            wire:model="correo"
                            placeholder="ejemplo@cepja.edu.sv"
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-950 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                        />
                        <x-input-error field="correo" />
                    </div>

                    <!-- Fecha de Ingreso -->
                    <div>
                        <label for="fecha_ingreso" class="block text-sm font-semibold text-slate-700">Fecha de Ingreso a la Institución</label>
                        <input 
                            type="date" 
                            id="fecha_ingreso"
                            wire:model="fecha_ingreso"
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-950 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                        />
                        <x-input-error field="fecha_ingreso" />
                    </div>

                    <!-- Especialidad (Solo Docentes) -->
                    @if($tipo === 'docente')
                        <div>
                            <label for="especialidad" class="block text-sm font-semibold text-slate-700">Especialidad Docente</label>
                            <input 
                                type="text" 
                                id="especialidad"
                                wire:model="especialidad"
                                placeholder="Ej: Ciencias Naturales, Matemática"
                                class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-950 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                            />
                            <x-input-error field="especialidad" />
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- System Account Linking column (1/3 width on large screens) -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs p-6 space-y-6">
                <h2 class="text-lg font-bold text-slate-900 border-b border-slate-100 pb-3">Cuenta de Sistema</h2>
                
                <div class="space-y-4">
                    <!-- Option Select -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Asociación de Cuenta</label>
                        
                        <div class="space-y-2.5">
                            <label class="relative flex items-center gap-2 cursor-pointer text-xs">
                                <input type="radio" wire:model.live="user_option" value="none" class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                                <span class="font-medium text-slate-750">No asociar usuario</span>
                            </label>

                            <label class="relative flex items-center gap-2 cursor-pointer text-xs">
                                <input type="radio" wire:model.live="user_option" value="existing" class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                                <span class="font-medium text-slate-750">Asociar cuenta existente</span>
                            </label>

                            @if(!$personalId)
                                <label class="relative flex items-center gap-2 cursor-pointer text-xs">
                                    <input type="radio" wire:model.live="user_option" value="new" class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                                    <span class="font-medium text-slate-750">Crear nueva cuenta de usuario</span>
                                </label>
                            @endif
                        </div>
                    </div>

                    <!-- Option details fields -->
                    @if($user_option === 'existing')
                        <div>
                            <label for="form_existing_user" class="block text-xs font-semibold text-slate-700 mb-1.5">Seleccionar Cuenta de Usuario</label>
                            <select 
                                id="form_existing_user"
                                wire:model="selected_existing_user_id"
                                class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-950 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                            >
                                <option value="">Seleccione un usuario...</option>
                                @foreach($this->getUnlinkedUsers() as $usr)
                                    <option value="{{ $usr->id }}">{{ $usr->name }} ({{ $usr->email }})</option>
                                @endforeach
                            </select>
                            <x-input-error field="selected_existing_user_id" />
                        </div>
                    @elseif($user_option === 'new')
                        <div class="space-y-4 border-t border-slate-100 pt-4">
                            <!-- New user password -->
                            <div>
                                <label for="new_user_password" class="block text-xs font-semibold text-slate-700">Contraseña Temporal</label>
                                <input 
                                    type="password" 
                                    id="new_user_password"
                                    wire:model="new_user_password"
                                    placeholder="Contraseña inicial"
                                    class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-950 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                                />
                                <x-input-error field="new_user_password" />
                            </div>

                            <!-- New user role -->
                            <div>
                                <label for="new_user_role" class="block text-xs font-semibold text-slate-700">Rol de Acceso</label>
                                <select 
                                    id="new_user_role"
                                    wire:model="new_user_role"
                                    class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-950 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                                >
                                    <option value="">Seleccione un rol...</option>
                                    @foreach($this->getRoles() as $role)
                                        <option value="{{ $role->name }}">{{ ucfirst($role->name) === 'Admin' ? 'Administrador' : ucfirst($role->name) }}</option>
                                    @endforeach
                                </select>
                                <x-input-error field="new_user_role" />
                            </div>
                        </div>
                    @endif
                </div>

                <div class="border-t border-slate-100 pt-5 space-y-4">
                    <!-- Activo -->
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="activo" class="h-4 w-4 rounded-sm border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                        <span class="ml-2 text-sm font-semibold text-slate-700">Expediente de Personal Activo</span>
                    </label>

                    <button 
                        type="submit"
                        class="w-full flex justify-center rounded-xl bg-indigo-600 hover:bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:shadow transition duration-150"
                    >
                        {{ $personalId ? 'Guardar Cambios' : 'Registrar Personal' }}
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
